<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
// Загружаем устанавливаемую базу данных
if (is_file(way(__DIR__.DS.'dbtables.php'))) require_once(way(__DIR__.DS.'dbtables.php'));

$install = new cInstall($config_php, $install_db, $install_parameters);
class cInstall 
{
	public $config_file;
	public $install_db;
	public $host;
	public $name;
	public $user;
	public $pass;
	public $demo;
	
	public function cInstall ($config_php, $install_db, $install_parameters) 
	{
		$this->config_file = $config_php;
		$this->install_db = $install_db;
	}
	public function set_parameters ($host, $name, $user, $pass, $demo) 
	{
		$this->host = $host;
		$this->name = $name;
		$this->user = $user;
		$this->pass = $pass;
		$this->demo = $demo;
	}
	
	public function uninstall () 
	{
		$this->config_delete();
		if ($this->demo) 
		{
			$this->db_clean();
		}
		else
		{
			$this->db_delete();
		}
		console_line('Перейти на <a href="/" class="install-item return-item">главную странцу</a>', 5);
	}
	
	public function install () 
	{
		$wrong_items = false;
		if ($this->demo) 
		{
			console_line('Установка демо-версии', 5);
			if (!$this->db_test()) $wrong_items = true;
		}
		else
		{
			if (!$this->db_create()) $wrong_items = true;
		}
		if (!$wrong_items) $this->db_fill();
		if (!$wrong_items) $this->config_create();
	}
	
	private function db_test () 
	{
		$db = new mysqli($this->host, $this->user, $this->pass, $this->name);
		if ($db->connect_error) 
		{
			console_line('Невозможно подключиться к базе данных: '.$db->connect_error, 5);
			return false;
		}
		else
		{
			console_line('Проверка базы данных прошла успешно', 5);
			return true;
		}
	}

	private function db_create () 
	{
		$db = new mysqli($this->host, $this->user, $this->pass);
		if ($db->connect_error) 
		{
			console_line('Невозможно подключиться к серверу баз данных: '.$db->connect_error, 5);
			console_line(PROGRAM_NAME.': невозможно установить.', 5);
			console_line('<a href="/" class="install-item return-item">Вернуться на главную</a>', 5);
			$result = false;
		}
		else 
		{
			$sql = 'CREATE DATABASE IF NOT EXISTS '.$this->name.';';
			$db->query($sql);
			if ($db->error) 
			{
				console_line('Ошибка при создании базы данных: '.$db->error, 5);
				$this->db_delete();
				$result = false;
			}
			else 
			{
				console_line('База данных успешно создана.', 5);
				$result = true;
			}
		}
		console_line('<a href="/" class="install-item return-item">Вернуться на главную</a>', 5);
		$db->close();
		return $result;
	}

	private function db_clean () 
	{
		$db = new mysqli($this->host, $this->user, $this->pass, $this->name);
		if ($db->connect_error) 
		{
			console_line('Невозможно подключиться к базе данных: '.$db->connect_error, 5);
			return false;
		}
		else 
		{
			if ((!empty($this->install_db))&&(is_array($this->install_db))) 
			{
				if ((!empty($this->install_db['tables']))&&(is_array($this->install_db['tables']))) 
				{
					foreach ($this->install_db['tables'] as $table_name => $table) 
					{
						if (empty($table['table_rows'])) 
						{
							console_line('Удаляем таблицу: '.$table_name, 5);
							$sql = 'DROP TABLE IF EXISTS '.$table_name.';';
							$db->query($sql);
						}
					}
				}
				else 
				{
					$result = false;
				}
			}
			else 
			{
				$result = false;
			}
		}
		$db->close();
	}

	private function db_fill () 
	{
		// Создаем таблицы базы данных
		$db = new mysqli($this->host, $this->user, $this->pass, $this->name);
		if ($db->connect_error) 
		{
			console_line('Невозможно подключиться к базе данных: '.$db->connect_error, 5);
			return false;
		}
		else 
		{
			if ((!empty($this->install_db))&&(is_array($this->install_db))) 
			{
				if ((!empty($this->install_db['tables']))&&(is_array($this->install_db['tables']))) 
				{
					foreach ($this->install_db['tables'] as $table_name => $table) 
					{
						console_line('Создаем таблицу: '.$table_name, 5);
						if ((!empty($table))&&(is_array($table))) 
						{
							$sql = 'CREATE TABLE IF NOT EXISTS '.$table_name.' (';
							$row_names = '';
							foreach ($table as $row_name => $row_attributes) 
							{
								if ((!empty($row_name))&&(!empty($row_attributes))&&(!is_array($row_attributes))) $sql .= $row_name.' '.$row_attributes.', ';
								if ((!empty($row_name))&&(!empty($row_attributes))&&(!is_array($row_attributes))&&($row_name!='id')) $row_names .= $row_name.', ';
							}
							$row_names = substr ($row_names, 0, -2);
							$sql = substr ($sql, 0, -2).');';
							$db->query($sql);
							if ($db->error) 
							{
								console_line('Ошибка при создании таблицы: '.$db->error, 5, 'error');
								$this->db_delete();
								return false;
							}
							else 
							{
								// Добавляем строки
								if ((!empty($table['table_rows']))&&(is_array($table['table_rows']))) 
								{
									$sql = '';
									$row_values = '';
									foreach ($table['table_rows'] as $arRow) 
									{
										$row_values .= '(';
										foreach ($arRow as $iRow) 
										{
											if ($iRow =='NULL') $row_values .= $iRow.','; else $row_values .= "'".$iRow."',";
										}
										$row_values = substr ($row_values, 0, -1).'),';
									}
									$row_values = substr ($row_values, 0, -1);
									$sql = 'INSERT INTO '.$table_name.' ('.$row_names.') VALUES '.$row_values;
									$db->query($sql);
									if ($db->error) 
									{
										console_line('Ошибка при заполнении таблицы: '.$db->error, 5);
										$this->db_delete();
										return false;
									}
								}
							}
						}
						else 
						{
							console_line('Ошибка при создании базы данных', 5);
							$this->db_delete();
							return false;
						}
					}
				}
				else 
				{
					console_line('Ошибка при создании базы данных', 5);
					$this->db_delete();
					return false;
				}
			}
			else 
			{
				console_line('Ошибка при создании базы данных', 5);
				$this->db_delete();
				return false;
			}
		}
	}
	
	private function db_delete () 
	{
		if (!empty($this->name)) 
		{
			$db = new mysqli($this->host, $this->user, $this->pass);
			if ($db->connect_error) 
			{
				console_line('Невозможно подключиться к базе данных', 5, 'error');
				$result = false;
			}
			$sql = 'DROP DATABASE '.$this->name.';';
			$db->query($sql);
			if ($db->error) 
			{
				console_line('Ошибка при удалении базы данных: <b>'.$db->error.'</b>. Попробуйте удалить ее вручную.', 5, 'error');
				$result = false;
			}
			else 
			{
				console_line('База данных удалена', 5, 'success');
				$result = true;
			}
			$db->close();
			return $result;
		}
		else 
		{
			console_line('Не найдена база данных. Попробуйте удалить ее вручную.', 5, 'error');
			return false;
		}
	}

	private function config_create () 
	{
		$file_content = "<?\n";
		$file_content .= "if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта\n";
		$file_content .= "define('DB_HOST', '".$this->host."');\n";
		$file_content .= "define('DB_USER', '".$this->user."');\n";
		$file_content .= "define('DB_PASS', '".$this->pass."');\n";
		$file_content .= "define('DB_NAME', '".$this->name."');\n";
		$file_content .= "define('DEMO', ".($this->demo ? 'true' : 'false').");\n";
		$file_content .= '?'.'>';
		file_put_contents($this->config_file, $file_content);
	}
	
	private function config_delete () 
	{
		console_line('Удаление '.PROGRAM_NAME, 5);
		if (is_file($this->config_file)) 
		{
			require_once($this->config_file);
			unlink($this->config_file);
			console_line('Файл конфигурации удален', 5, 'success');
			return true;
		}
		else 
		{
			console_line('Отсутствует файл конфигурации', 5, 'error');
			return false;
		}
	}
}

// Вывод результатов и завершение установки
$page = new class 
{
	public $element = array
	(
		'html' => SCRIPTS.'tpl'.DS.'html.tpl.php', 
		'menu' => SCRIPTS.'tpl'.DS.'menu.tpl.php', 
		'foot' => SCRIPTS.'tpl'.DS.'foot.tpl.php'
	);
};

if (!defined('AJAX_FORM')) 
{
	require_once($page->element['html']);
	die();
}
?>