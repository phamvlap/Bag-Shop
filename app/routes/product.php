<?php 

$router->get(
	'/',
	'App\Controllers\ProductsController@index'
);
$router->get(
	'/home',
	'App\Controllers\ProductsController@index'
);
$router->get(
	'/view/item/(\d+)',
	'App\Controllers\ProductsController@viewItem'
);
$router->post(
	'/search/get-hint',
	'App\Controllers\ProductsController@getHint'
);
$router->get(
	'/search',
	'App\Controllers\ProductsController@search'
);
$router->post(
	'/get-item',
	'App\Controllers\ProductsController@getItem'
); 
