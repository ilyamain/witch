<?
// Загрузка системных файлов, модулей. Или установка программы
require_once ($_SERVER[DOCUMENT_ROOT].DIRECTORY_SEPARATOR.'load'.DIRECTORY_SEPARATOR.'start.php');
// Загрузка шаблона страницы и вывод на экран
require_once ($page->element['html']);
?>