<?php

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\{Product, Paginator};

class AdminController extends Controller {
	private int $numberOfItemsPerPage = 8;

	# show admin home page
	public function index() {
		if(!isset($_SESSION['admin'])) {
			redirectTo('/admin');
		}

		$productModel = new Product();

		$limit = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? (int)$_GET['limit'] : $this->numberOfItemsPerPage;
		$page = (isset($_GET['page']) && is_numeric($_GET['page'])) ? (int)$_GET['page'] : 1;

		$paginator = new Paginator(
			recordsPerPage: $limit, 
			totalRecords: $productModel->count(), 
			currentPage: $page
		);

		$products = $productModel->paginate(offset: $paginator->getRecordOffset(), limit: $paginator->getRecordsPerPage());
		$pages = $paginator->getPages();

		$pagination = [
			'limit' => $paginator->getRecordsPerPage(),
			'prevPage' => $paginator->getPrevPage(),
			'currPage' => $paginator->getCurrPage(),
			'nextPage' => $paginator->getNextPage(),
			'pages' => $pages
		];
		
		renderPage('/admin/home/index.php', [
			'products' => $products,
			'pagination' => $pagination
		]);
	}

	# login admin
	public function create() {
		if(!isset($_SESSION['admin'])) {
			renderPage('/admin/login/index.php');
		}
		else {
			redirectTo('/admin/product');
		}
	}

	# store login admin
	public function store() {
		$keys = ['admin-email', 'admin-password'];

		$data = $this->filterData(keys: $keys, data: $_POST);

		$this->saveFormValues(data: $data, except: ['admin-password']);

		$errors = [];

		if($data['admin-email'] === '') {
			$errors['admin-email'] = 'Email không được bỏ trống';
		}
		elseif($data['admin-email'] !== 'admin@gmail.com') {
			$errors['admin-email'] = 'Email không chính xác';
		}

		if($data['admin-password'] === '') {
			$errors['admin-password'] = 'Mật khẩu không được bỏ trống';
		}
		elseif($data['admin-password'] !== 'admin@123') {
			$errors['admin-password'] = 'Mật khẩu không chính xác';
		}

		if(count($errors) > 0) {
			renderPage('/admin/login/index.php', ['admin-errors' => $errors]);
		}
		else {
			$admin['email'] = $data['admin-email'];
			redirectTo('/admin/product', ['admin' => $admin]);
		}
	}
	
	# extit login admin
	public function destroy() {
		session_unset();
		session_destroy();

		redirectTo('/admin');
	}
}