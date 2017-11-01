<?
if (!defined('PROGRAM_NAME')) die(); // Защита от прямого вызова скрипта
$arTables = array
(
	'blocks' => array
	(
		'id' => 'int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY', 
		'number' => 'tinytext NOT NULL', 
		'content' => 'text NOT NULL', 
		'table_rows' => array (),
	),
);

// тестовые блоки
$blocks = array();
$blocks[0]  = '*b:{"n":"0","p":"genesis","h":"genesis","t":"1504224000"}'.PHP_EOL;
$blocks[0] .= '*i:["premine_invest","a254488e155ba7da90d1033fa7502611e82287d283f9d80f70962768670fa941","ar","20000000.00000000"]'.PHP_EOL;
$blocks[0] .= '*q:["10","10","10","10","10","10","10","10","10","10"]'.PHP_EOL;
$blocks[0] .= '*p:["0","0","1","0.00000000","0.00000000","1","170","82bf7e374f9e438f043063aaf23ccf53fa8b217ec90ccad5ea1aacada6b92e94","ar","20000000.00000000"]'.PHP_EOL;

$blocks[1]  = '*b:{"n":"1","p":"0","h":"82bf7e374f9e438f043063aaf23ccf53fa8b217ec90ccad5ea1aacada6b92e94","t":"1504225200"}'.PHP_EOL;
$blocks[1] .= '>bco:["p06QK2K8My8OqkBL","Dn9z6wE5hEXzldZFuadbjviI5jo7XBr5jMs4EQQaaJ0gfvO6atVfFxNZG1R6MjtS","e5c518df1b56c4d1e2a63f10701347ec3c7057681b026e388989f30f81e1d180","ar","1506378147","15.00000000"]'.PHP_EOL;
$blocks[1] .= '@bai:["pr84iY9GAnXJ6akt","631f14412784fb62996294865f466ab70773c48b03934a2ec0c8bd8534de6189","13290a5f3d19e133f241f1fe2074805e5db5b648408cfd80a7e9a2529eb46cca"]'.PHP_EOL;
$blocks[1] .= '@bai:["pfhOfMKes36xNTJh","14a8dd818c7ba22d156aa2c0b0137026b28256fc497bcf221aa23f730e3a7020","0900e5af2f425f3c03c2a98940279013bc072366cc388260628a69a649eef259"]'.PHP_EOL;
$blocks[1] .= '*i:["w15023655311B3d5F7h9J","a254488e155ba7da90d1033fa7502611e82287d283f9d80f70962768670fa941","ar","64.23074124"]'.PHP_EOL;
$blocks[1] .= '*q:["10","10","10","9","9","9","11","11","10","11"]'.PHP_EOL;
$blocks[1] .= '*p:["1","2","67","20000.00000000","40000.00000000","67","731","273c35b7d2c1457287348ea8542c9ed78661f2e5c4aa80d9651fb2442bd932be","ar","64.23074124"]'.PHP_EOL;

$blocks[2]  = '*b:{"n":"2","p":"1","h":"273c35b7d2c1457287348ea8542c9ed78661f2e5c4aa80d9651fb2442bd932be","t":"1504226400"}'.PHP_EOL;
$blocks[2] .= '>bs:["pr84iY9GAnXJ6akt","MmAvYhb8iM49VxuyGFb9IuKKj7eMfiIPqXw7lAz1yS5wjojMlRr0cg4dTtOYfVwh",[{"n":"fooo","s":"be69823fdafe89688c1292a81efb0b3455cf498c5c99485667e418e603696839","a":"ar","d":"500.00000000"},{"n":"yohoho","s":"1d8891d41869a75ec86c5402e0bede041374960fee9984503a3f4346c1c09421","a":"ar","d":"19050.00000000"}],"1506378486","450.00000000"]'.PHP_EOL;
$blocks[2] .= '@bai:["p0MhMW71AQnAsXwz","0fa74a1099a49233aabb338e70ada7a81cc1d5d12fab78ec389f654821808160","b87c4a0add0d2ab9accb9bf09a858152b152450ee5e82589d3157cbb4a63c542"]'.PHP_EOL;
$blocks[2] .= '@bai:["pOkQad5UzSwAaIKW","61441289164c6206b6270d49025e766cf166ae5884c20a4d5d93c8a65be166f5","f70a60503e404845fbc8634ef12db640059d912605d8907a3138d23ba192d36c"]'.PHP_EOL;
$blocks[2] .= '@bai:["pya13BVsA2fybgrf","fdff92e71430fdf8c6fffefcdaa8246f72fcb0f84e23afc6fca8b6633e099c00","acc99b651107768e374d0d7fbba9673658c561790a5f17f1b996976d75c715fb"]'.PHP_EOL;
$blocks[2] .= '*i:["w15023668331B3d5F7h9J","a254488e155ba7da90d1033fa7502611e82287d283f9d80f70962768670fa941","ar","499.23056188"]'.PHP_EOL;
$blocks[2] .= '*q:["10","9","11","9","8","10","12","10","10","11"]'.PHP_EOL;
$blocks[2] .= '*p:["1","3","50","20000.00000000","60000.00000000","50","1049","d0fb31a238ad529b8ad18054da62a9428812c11a5cb51a5e614f3861ffe1c9b0","ar","499.23056188"]'.PHP_EOL;

$blocks[3]  = '*b:{"n":"3","p":"2","h":"d0fb31a238ad529b8ad18054da62a9428812c11a5cb51a5e614f3861ffe1c9b0","t":"1504227600"}'.PHP_EOL;
$blocks[3] .= '>br:[[{"n":"pfhOfMKes36xNTJh","k":"FGZRSWpbRj5uOJD06VbfMxCUZZkNE1DZ5tvcuuvOHSRlOBLZqEOBemW94DQ0Ojg5"},{"n":"pya13BVsA2fybgrf","k":"uJI13heZxgsEIQDqq5PYmv8uwng8d3qZPeh5O0b0IZ7M0h4Glo13QD9DbBe04pjf"}],[{"n":"bar","s":"32f37ec84e2a20f151518a43470a8139fe3831c1f207e740a7f6c4b225ae98ab","a":"ar","d":"9900.00000000"},{"n":"foo","s":"d7b8e9db18e1eee9fd80a413120a7a553f71b39c38bbf50d9e410f2d8e3020bb","a":"ar","d":"30000.00000000"}],"1506378350","100.00000000"]'.PHP_EOL;
$blocks[3] .= '>bco:["pOkQad5UzSwAaIKW","DyT5I6Qqcwn5Y1yi2lG9WQ85zvjt8j1QE6OVKq1djRNrmlCW8TpLlRn41zCM2QX6","a7025cd58cfb4e5dc2be7911e4bc8fbbbae511977981a04528d4da9369e9fb91","t","1506378700","0.25648962"]'.PHP_EOL;
$blocks[3] .= '@bai:["pQeJ6yhniKFs4E6j","0eda2661349c88060cd83a22c896f9899316a901ece227dedda0391f6d7af48c","eea88458098a63f2247cba59c4d0695e54a9345faf098b3581e0df26f801b626"]'.PHP_EOL;
$blocks[3] .= '@bai:["pbulSaQT6Twl0Tsk","29f4777f429d0411941dedf6bd2abc6ae975c02a21ac3ce900f7ba710afb3225","8161566a3b910527835a85321fde184a7892aa1dc5e8c5e9e7bdcb3454df2d22"]'.PHP_EOL;
$blocks[3] .= '@bai:["pW13cbnAyn1iEigN","88006bbcc49e2c00d27045cd8f24e8a09c5395d0584a2d370e07faf9d860ca9c","ae18d0d8816d2cd0e019fd1510b9eee9c3de56626601bf31bf4e6c2ac29c41d8"]'.PHP_EOL;
// специально намерение с ошибкой
$blocks[3] .= '@bai:["pbulSaQT6Twl0Tsk","29f4777f429d0411941dedf6bd2abc6ae975c02a21ac3ce900f7ba710afb3225","8161566a3b910527835a85321fde184a7892aa1dc5e8c5e9e7bdcb3454df2d22"]'.PHP_EOL;
$blocks[3] .= '*i:["w15023668331B3d5F7h9J","a254488e155ba7da90d1033fa7502611e82287d283f9d80f70962768670fa941","ar","149.48687213"]'.PHP_EOL;
$blocks[3] .= '*q:["9","9","10","9","10","9","13","11","10","10"]'.PHP_EOL;
$blocks[3] .= '*p:["2","4","67","60000.00000000","80000.00000000","86","1502","bc671dbc80af6c321f4c481f8eebfa9a37ac4e851661811e46969a7b04314c5e","ar","149.48687213"]'.PHP_EOL;

$blocks[4]  = '*b:{"n":"4","p":"3","h":"bc671dbc80af6c321f4c481f8eebfa9a37ac4e851661811e46969a7b04314c5e","t":"1504228800"}'.PHP_EOL;
$blocks[4] .= '>bu:[[{"n":"p0MhMW71AQnAsXwz","k":"Uh6dgHVOtrJsb3MJfokuEuTFt90ZFxyvAMXH4eN6gQJH7LutCkqRnhllMop4FBEk"},{"n":"pW13cbnAyn1iEigN","k":"rykPm006SpEk1srhATSIZQU3Ep9aEb90JVrYf4HdhPCsFkK6UM3ODOIDf90kLPT9"}],"helloworld","6f13e921899ed486f01a3d88557c7c90ba015a8d8ed58172d366073c09d9392d","ar","1506378227","1.56433000"]'.PHP_EOL;
$blocks[4] .= '>bs:["pQeJ6yhniKFs4E6j","94Zbg09hvhBXWmdYVV9RSZ4nHtW6lbrs6kmUDF98HvUP3QGfylR4GIeAlOJAeZ0z",[{"n":"kkk","s":"40ab114f930d9afc408124b4c85101283228edd3f2e7d81f64677d6dd13a7d0b","a":"ar","d":"4999.00000000"},{"n":"qqq","s":"04d1a3e32791927d9e9191d0362f4023461c660dbfd378073b5b936e82b2018a","a":"ar","d":"15000.00000000"}],"1506378600","1.00000000"]'.PHP_EOL;
$blocks[4] .= '@no:[]'.PHP_EOL;
$blocks[4] .= '*i:["w15023668331B3d5F7h9J","a254488e155ba7da90d1033fa7502611e82287d283f9d80f70962768670fa941","ar","51.79453315"]'.PHP_EOL;
$blocks[4] .= '*q:["9","10","10","9","11","10","12","10","10","9"]'.PHP_EOL;
$blocks[4] .= '*p:["2","1","67","60000.00000000","0.00000000","0","880","760fb828ebdf3adc7d4dea616ced9a71073c4d78f658e958971a66aabf0c25d4","ar","51.79453315"]'.PHP_EOL;

foreach ($blocks as $key => $block) array_push ($arTables['blocks']['table_rows'], [$key, $block]);
?>