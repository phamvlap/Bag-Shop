<?php 

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Models\{Product, Paginator};

class ManageProductsController extends Controller {
	private int $numberOfItemsPerPage = 8;

	# load item's add page
	public function create() {
		renderPage('/admin/add/index.php');
	}

	# store item into products table
	public function store() {
		$keys = ['item-name', 'item-desc', 'item-price', 'item-type'];
		$data = $this->filterData(keys: $keys, data: $_POST);
		$data['item-files'] = $_FILES['item-files'];

		$this->saveFormValues(data: $data);

		$resCheckValues = $this->checkValuesForm($data);

		$errors = $resCheckValues['errors'];
		$newImages = $resCheckValues['new-images'];

		if(count($errors) > 0) {
			renderPage('/admin/add/index.php', [
				'old' => $this->getSavedFormValues(),
				'errors' => $errors
			]);
		}
		else {
			$saveImages = join(';', $newImages);
			$product = new Product();
			$fillableData = [
				'name' => $data['item-name'],
				'describes' => $data['item-desc'],
				'images' => $saveImages,
				'type' => $data['item-type'],
				'price' => $data['item-price']
			];

			$product->fill($fillableData);

			if($product->add()) {
				redirectTo('/admin', [
					'message-success' => "{$data['item-name']} đã được thêm thành công"
				]);
			}
			else {
				redirectTo('/admin', [
					'message-failed' => "Thêm sản phẩm {$data['item-name']} thất bại!"
				]);
			}
		}
	}

	# load item's update page
	public function edit(int $id) {
		$product = new Product();
		$item = $product->findByID(id: $id);

		renderPage('/admin/detail_item/index.php', [
			'item' => $item
		]);
	}

	# store updated item into products table
	public function update(int $id) {
		$productModel = new Product();
		$item = $productModel->findByID(id: $id);

		$keys = ['item-name', 'item-desc', 'item-price', 'item-type'];
		$data = $this->filterData(keys: $keys, data: $_POST);
		if(isset($_FILES['item-files']) && strlen($_FILES['item-files']['name'][0])> 0) {
			$data['item-files'] = $_FILES['item-files'];
		}

		$this->saveFormValues(data: $data);

		$resCheckValues = $this->checkValuesForm($data);

		$errors = $resCheckValues['errors'];
		$newImages = $resCheckValues['new-images'];

		if(!isset($data['item-files']) && strlen($item['images']) > 0) {
			unset($errors['item-files']);
		}

		if(count($errors) > 0) {
			renderPage('/admin/detail_item/index.php', [
				'old' => $this->getSavedFormValues(),
				'errors' => $errors
			]);
		}
		else {
			$saveImages = join(';', $newImages);

			$updatedFields = [];

			if($item['name'] !== $data['item-name']) {
				$updatedFields['name'] = $data['item-name'];
			}
			if($item['describes'] !== $data['item-desc']) {
				$updatedFields['describes'] = $data['item-desc'];
			}
			if(isset($data['item-files']) && $item['images'] !== $saveImages) {
				$updatedFields['images'] = $saveImages;
			}
			if($item['type'] !== (int)$data['item-type']) {
				$updatedFields['type'] = $data['item-type'];
			}
			if($item['price'] !== (int)$data['item-price']) {
				$updatedFields['price'] = $data['item-price'];
			}

			if(count($updatedFields) > 0) {
				if($productModel->edit(id: $id, updatedFields: $updatedFields)) {
					redirectTo('/admin', [
						'message-success' => "{$item['name']} đã được cập nhật thành công"
					]);
				}
				else {
					redirectTo('/admin', [
						'message-failed' => "Cập nhật sản phẩm {$item['name']} thất bại!"
					]);
				}
			}
			else {
				redirectTo('/admin', [
					'message-success' => "Không có thay đổi nào trong sản phẩm {$item['name']}"
				]);
			}	
		}		
	}

	# show confirm delete item
	public function confirmDelete(int $id) {
		$productModel = new Product();
		$item = $productModel->findByID(id: $id);

		if($item) {
			renderPage('/admin/home/index.php', [
				'delete-item' => $item
			]);
		}
	}

	# delete item
	public function destroy(int $id) {
		$productModel = new Product();
		$item = $productModel->findByID(id: $id);

		if($productModel->remove($id)) {
			redirectTo('/admin/product', [
				'message-success' => "Xóa sản phẩm {$item['name']} thành công"
			]);
		}
		else {
			redirectTo('/admin/product', [
				'message-failed' => "Thất bại khi xóa sản phẩm {$item['name']}"
			]);
		}
	}

	# check form values
	public function checkValuesForm(array $data) {
		$errors = [];

		if(strlen($data['item-name']) === 0) {
			$errors['item-name'] = 'Tên sản phẩm không được bỏ trống';
		}

		if(strlen($data['item-desc']) === 0) {
			$errors['item-desc'] = 'Mô tả sản phẩm không được bỏ trống';
		}

		if(strlen($data['item-price']) === 0) {
			$errors['item-price'] = 'Giá sản phẩm không được bỏ trống';
		}

		if((int)$data['item-price'] < 1000) {
			$errors['item-price'] = 'Giá sản phẩm không hợp lệ';
		}

		if((int)$data['item-type'] === 0) {
			$errors['item-type'] = 'Loại sản phẩm không được bỏ trống';
		}

		$newImages = [];
		if(count($errors) === 0) {
			// upload images
			$targetDir = __DIR__ . '/../../../public/uploads/';
			$extensions = ['jpg', 'jpeg', 'png', 'gif'];

			if(isset($data['item-files']) && strlen($data['item-files']['name'][0]) > 0) {
				for($i = 0; $i < count($data['item-files']['name']); ++$i) {
					$imageFileName = $data['item-files']['name'][$i];
					$checkImageSize = getimagesize($data['item-files']['tmp_name'][$i]);
					$imageFileType = strtolower(pathinfo($imageFileName, PATHINFO_EXTENSION));

					// check file image
					if($checkImageSize === false) {
						$errors['item-files'] = "{$imageFileName} không phải là hình ảnh!";
						break;
					}

					// check size image
					if($data['item-files']['size'][$i] > 500000) {
						$errors['item-files'] = "{$imageFileName} có kích thước quá lớn!";
						break;
					}

					// check allowed extensions
					if(!in_array($imageFileType, $extensions)) {
						$errors['item-files'] = "{$imageFileName} không thuộc định dạng hình ảnh cho phép! Chỉ hình ảnh JPG, JPEG, PNG, GIF là được cho phép tải lên";
						break;
					}

					// save image to new destination
					if(!isset($errors['item-files'])) {
						$newImageName = bin2hex(random_bytes(4));

						$targetFile = $targetDir . $newImageName . ".{$imageFileType}";

						if(move_uploaded_file($data['item-files']['tmp_name'][$i], $targetFile)) {
							array_push($newImages, $newImageName . ".{$imageFileType}");
						}
						else {
							$errors['item-files'] = "{$imageFileName} có lỗi trong quá trình tải lên";
						}
					}
					else {
						break;
					}
				}
			}
			else {
				$errors['item-files'] = "Không tìm thấy hình ảnh tải lên!";
			}
		}

		return [
			'errors' => $errors,
			'new-images' => $newImages
		];
	}

	# filter products from filter 
	public function filter() {
		$keys = ['filter-type', 'filter-price', 'filter-date'];
		$data = $this->filterData(keys: $keys, data: $_GET);

		$productModel = new Product();

		$filters = [];
		$orders = [];

		if(isset($data['filter-type']) && $data['filter-type'] !== 'none') {
			$filters['type'] = $data['filter-type']; 
		}
		if(isset($data['filter-price']) && $data['filter-price'] !== 'none') {
			$orders['price'] = $data['filter-price']; 
		}
		if(isset($data['filter-date']) && $data['filter-date'] !== 'none') {
			$orders['updated_at'] = $data['filter-date']; 
		}

		$limit = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? (int)$_GET['limit'] : $this->numberOfItemsPerPage;
		$page = (isset($_GET['page']) && is_numeric($_GET['page'])) ? (int)$_GET['page'] : 1;

		$paginator = new Paginator(
			recordsPerPage: $limit, 
			totalRecords: $productModel->countFilterResult(filters: $filters), 
			currentPage: $page
		);

		$products = $productModel->paginateWithFilter(filters: $filters, orders: $orders, limit: $paginator->getRecordsPerPage(), offset: $paginator->getRecordOffset());

		$pages = $paginator->getPages();

		$pagination = [
			'limit' => $limit,
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

	# show detail item
	public function viewItem(int $id) {
		$product = new Product();
		$item = $product->findByID(id: $id);

		renderPage('/admin/detail_item/index.php', [
			'item' => $item
		]);
	}

	# search products
	public function search() {
		$productModel = new Product();

		$key = isset($_GET['key']) ? $_GET['key'] : '';
		$keys = ['filter-type', 'filter-price', 'filter-date'];
		$data = $this->filterData(keys: $keys, data: $_GET);

		$filters = [];
		$orders = [];

		if(isset($data['filter-type']) && $data['filter-type'] !== 'none') {
			$filters['type'] = $data['filter-type']; 
		}
		if(isset($data['filter-price']) && $data['filter-price'] !== 'none') {
			$orders['price'] = $data['filter-price']; 
		}
		if(isset($data['filter-date']) && $data['filter-date'] !== 'none') {
			$orders['updated_at'] = $data['filter-date']; 
		}

		$page = (isset($_GET['page']) && is_numeric($_GET['page'])) ? (int)$_GET['page'] : 1;
		$limit = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? (int)$_GET['limit'] : $this->numberOfItemsPerPage;

		$totalRecords = $productModel->countSearchResult(name: $key);

		$paginator = new Paginator(
			recordsPerPage: $limit, 
			totalRecords: $totalRecords, 
			currentPage: $page
		);

		$products = $productModel->searchWithFilter(name: $key, filters: $filters, orders: $orders, offset: $paginator->getRecordOffset(), limit: $limit);

		$pages = $paginator->getPages(length: min($paginator->getTotalPages(), 3));

		$pagination = [
			'limit' => $limit,
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
}