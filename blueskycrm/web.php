<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuperadminController;
use App\Http\Controllers\CompanyadminController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\UserController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
// MAIN WEBSITE 
Route::get('/', function () {
    return view('frontend.index');
})->name('home');

Route::get('/about', function () {
    return view('frontend.about');
})->name('about');




Route::get('/anchors', function () {
    return view('frontend.anchorage');
})->name('anchors');

Route::get('/literature', function () {
    $products = DB::select('select * from products');
    $lr = DB::select('select * from product_literaturereview');

    return view('frontend.literature', compact('products','lr'));
})->name('literature');


Route::get('/locations', function () {
    $locations = DB::select('select * from locations');

    return view('frontend.locations', compact('locations'));
})->name('locations');


Route::get('/tiedown', function () {
    return view('frontend.tiedown');
})->name('tiedown');

Route::get('/framing-hardware', function () {
    return view('frontend.framing-hardware');
})->name('framing-hardware');

Route::get('/custom-steel', function () {
    return view('frontend.custom-steel');
})->name('custom-steel');

Route::get('/weatherization-systems', function () {
    return view('frontend.weatherization-systems');
})->name('weatherization-systems');


Route::get('/fasbox', function () {
    return view('frontend.fasbox');
})->name('fasbox');


Route::get('/rfi-assistance', function () {
    return view('frontend.rfi-assistance');
})->name('rfi-assistance');


Route::get('/engineered-designed-solutions', function () {
    return view('frontend.engineered-designed-solutions');
})->name('engineered-designed-solutions');

Route::get('/products', function () {
    $searchTerm = request()->input('q');

    $products = DB::table('products')
        ->leftJoin('category', 'products.category_id', '=', 'category.id')
        ->select('products.*', 'category.category_name');

    // Apply search filter if a search term is provided
    if ($searchTerm) {
        $products->where('products.product_name', 'like', "%$searchTerm%");
    }

    $products = $products->get();
    $categories = DB::select('select * from category');

    return view('frontend.products', compact('products', 'categories', 'searchTerm'));
})->name('products');

Route::get('/filterproducts/{type}', function ($type) {

    // Get all product IDs from the product_literaturereview table where type matches
    $productIds = DB::table('product_literaturereview')
        ->where('type', $type)
        ->pluck('product_id');

    // Fetch products from the products table where the ID matches the retrieved IDs
    $products = DB::table('products')
        ->whereIn('id', $productIds)
        ->get();


    return view('frontend.filterproducts', compact('products'));
})->name('filterproducts');


Route::get('/product/{id}', function ($id) {
    $product = DB::table('products')
        ->where('id', '=', $id)
        ->first();
    $literaturereview = DB::table('product_literaturereview')
        ->where('product_id', '=', $id)
        ->get();

$variants = DB::table('attribute_products')
    ->where('product_id', '=', $id)
    ->orderBy('sort_id', 'asc')
    ->get();


    $attributes = DB::select('select * from attributes');    
    $array = explode(',', $product->attributes);


    $data = array();
    foreach($array as $table_name) {
$data[$table_name] = DB::table($table_name)
    ->orderBy('sort_id', 'asc')
    ->get();
    }         
    return view('frontend.product', compact('product','variants','attributes','data','array','literaturereview'));
})->name('productpage');


Route::get('/sproduct/{id}', function ($id) {
    $product = DB::table('products')
        ->where('id', '=', $id)
        ->first();
    $literaturereview = DB::table('product_literaturereview')
        ->where('product_id', '=', $id)
        ->get();

$variants = DB::table('attribute_products')
    ->where('product_id', '=', $id)
    ->orderBy('sort_id', 'asc')
    ->get();
       
    return view('frontend.sproduct', compact('product','variants','literaturereview'));
})->name('sproductpage');

require __DIR__.'/auth.php';


// SUPERADMIN ROUTE

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/dashboard',[SuperadminController::class, 'SuperadminDashboard'])->name('superadmin.dashboard'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/logout',[SuperadminController::class, 'SuperadminDestroy'])->name('superadmin.logout'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/profile',[SuperadminController::class, 'SuperadminProfile'])->name('superadmin.profile'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::post('/store/profile',[SuperadminController::class, 'StoreProfile'])->name('store.profile'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/create_user',[SuperadminController::class, 'CreateUser'])->name('superadmin.create_user'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::post('/superadmin/storeuser',[SuperadminController::class, 'StoreUser'])->name('superadmin.storeuser'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/view_users',[SuperadminController::class, 'ViewUser'])->name('superadmin.view_users'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/delete_user/{id}/{role}',[SuperadminController::class, 'DeleteUser'])->name('superadmin.delete_user'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/create_company',[SuperadminController::class, 'CreateCompany'])->name('superadmin.create_company'); });


Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::post('/superadmin/storecompany',[SuperadminController::class, 'StoreCompany'])->name('superadmin.storecompany'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/view_company',[SuperadminController::class, 'ViewCompany'])->name('superadmin.view_company'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/delete_company/{id}',[SuperadminController::class, 'DeleteCompany'])->name('superadmin.delete_company'); });


Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::post('/superadmin/store_edit/{id}',[SuperadminController::class, 'StoreEdit'])->name('superadmin.store_edit'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/edit_company/{id}',[SuperadminController::class, 'EditCompany'])->name('superadmin.edit_company'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/assign_role',[SuperadminController::class, 'AssignRole'])->name('superadmin.assign_role'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::post('/superadmin/storerole',[SuperadminController::class, 'StoreRole'])->name('superadmin.storerole'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/create_category',[SuperadminController::class, 'CreateCategory'])->name('superadmin.create_category'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/manage_parentcategory',[SuperadminController::class, 'ManageParentCategory'])->name('superadmin.manage_parentcategory'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::post('/superadmin/storecategory',[SuperadminController::class, 'StoreCategory'])->name('superadmin.storecategory'); });
Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::post('/superadmin/storeparentcategory',[SuperadminController::class, 'StoreParentCategory'])->name('superadmin.storeparentcategory'); });


Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/view_category',[SuperadminController::class, 'ViewCategory'])->name('superadmin.view_category'); });


Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/delete_category/{id}',[SuperadminController::class, 'DeleteCategory'])->name('superadmin.delete_category'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/delete_parentcategory/{id}',[SuperadminController::class, 'DeleteParentCategory'])->name('superadmin.delete_parentcategory'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/create_attributes',[SuperadminController::class, 'CreateAttributes'])->name('superadmin.create_attributes'); });


Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/location',[SuperadminController::class, 'CreateLocation'])->name('superadmin.location'); });


Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/all_literature',[SuperadminController::class, 'AllLiterature'])->name('superadmin.all_literature'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/create_literature',[SuperadminController::class, 'CreateLiterature'])->name('superadmin.create_literature'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::post('/superadmin/storeliteraturereview',[SuperadminController::class, 'StoreLiteratureReview'])->name('superadmin.storeliteraturereview'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/delete_literaturereview/{id}',[SuperadminController::class, 'DeleteLiteratureReview'])->name('superadmin.delete_literaturereview'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::post('/superadmin/storeattribute',[SuperadminController::class, 'StoreAttribute'])->name('superadmin.storeattribute'); });


Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::post('/superadmin/storelocation',[SuperadminController::class, 'StoreLocation'])->name('superadmin.storelocation'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::post('/superadmin/storeeditlocation/{id}',[SuperadminController::class, 'StoreEditLocation'])->name('superadmin.storeeditlocation'); });


Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/edit_location/{id}',[SuperadminController::class, 'EditLocation'])->name('superadmin.edit_location'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/delete_location/{id}',[SuperadminController::class, 'DeleteLocation'])->name('superadmin.delete_location'); });



Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/delete_attribute/{id}',[SuperadminController::class, 'DeleteAttribute'])->name('superadmin.delete_attribute'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/manage_orders',[SuperadminController::class, 'ManageOrders'])->name('superadmin.manage_orders'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/view_order/{id}',[SuperadminController::class, 'ViewOrder'])->name('superadmin.view_order'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::post('/superadmin/store_status/{id}',[SuperadminController::class, 'StoreStatus'])->name('superadmin.store_status'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/view_order/{id}', [Company::class, 'ViewOrder'])->name('superadmin.store_status'); });


Route::get('/superadmin/login',[SuperadminController::class, 'SuperadminLogin']);

Route::middleware(['auth', 'role:superadmin'])->group(function() {
    Route::get('/fetch-default-price/{companyId}/{productId}', [SuperadminController::class, 'fetchDefaultPrice']);
});

Route::middleware(['auth', 'role:superadmin'])->group(function() {
    Route::get('/fetch-attributes-by-product/{productId}', [SuperadminController::class, 'fetchAttributesByProduct']);

});
Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/create_orders',[SuperadminController::class, 'CreateOrders'])->name('superadmin.create_orders'); });

// Route::middleware(['auth', 'role:superadmin'])->group(function () {
//     Route::get('/superadmin/create_orders', [SuperadminController::class, 'showCreateOrderForm'])->name('superadmin.show_create_order_form');

//     Route::post('/superadmin/store_order', [SuperadminController::class, 'storeOrder'])->name('superadmin.store_order');

//     Route::get('/superadmin/manage_orders', [SuperadminController::class, 'showManageOrders'])->name('superadmin.show_manage_orders');
// });


// BACKEND PRODUCT PAGE
Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/view_products',[SuperadminController::class, 'ViewProducts'])->name('superadmin.view_products'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/add_product',[SuperadminController::class, 'AddProduct'])->name('superadmin.add_product'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/add_simple_product',[SuperadminController::class, 'AddSimpleProduct'])->name('superadmin.add_simple_product'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/upload_csv',[SuperadminController::class, 'UploadCsv'])->name('superadmin.upload_csv'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::Post('/superadmin/import_csv',[SuperadminController::class, 'ImportCsv'])->name('superadmin.import_csv'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/import_csv',[SuperadminController::class, 'ImportCsv'])->name('superadmin.import_csv'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::post('/superadmin/editproductattribute/{id}',[SuperadminController::class, 'EditProductAttribute'])->name('superadmin.editproductattribute'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/edit_product/{id}',[SuperadminController::class, 'EditProduct'])->name('superadmin.edit_product'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/edit_simpleproduct/{id}',[SuperadminController::class, 'EditSimpleProduct'])->name('superadmin.edit_simpleproduct'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/delete_product/{id}',[SuperadminController::class, 'DeleteProduct'])->name('superadmin.delete_product'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/delete_productatt/{product_id}/{id}',[SuperadminController::class, 'DeleteAttProduct'])->name('superadmin.delete_productatt'); });

Route::middleware(['auth', 'role:superadmin'])->group(function () {
    Route::get('/get-attributes/{productid}', [SuperAdminController::class, 'getAttributes'])
        ->name('superadmin.get_attributes');
});

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::post('/superadmin/StoreAttributeSort/{attribute}/{id}',[SuperadminController::class, 'StoreAttributeSort'])->name('superadmin.store_attributesort'); });


Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/edit_attribute/{id}',[SuperadminController::class, 'EditAttribute'])->name('superadmin.edit_attribute'); });

Route::middleware(['auth','role:superadmin'])->group(function() {
    Route::post('/superadmin/update_attribute', [SuperadminController::class, 'updateAttribute'])->name('superadmin.update_attribute');
});


Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/edit_attributevalue/{attribute}/{id}',[SuperadminController::class, 'EditAttributeValue'])->name('superadmin.edit_attributevalue'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/delete_attributevalue/{attribute}/{id}',[SuperadminController::class, 'DeleteAttributeValue'])->name('superadmin.delete_attributevalue'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::post('/superadmin/storeattvalues/{id}',[SuperadminController::class, 'StoreAttributeValues'])->name('superadmin.storeattvalues'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::post('/superadmin/storedit_attributevalue/{attribute}/{id}',[SuperadminController::class, 'StoreEditAttributeValues'])->name('superadmin.storedit_attributevalue'); });


Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/closepopup',[SuperadminController::class, 'closepopup'])->name('superadmin.closepopup'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::post('/superadmin/storeproduct',[SuperadminController::class, 'StoreProduct'])->name('superadmin.storeproduct'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::post('/superadmin/storesimpleproduct',[SuperadminController::class, 'StoreSimpleProduct'])->name('superadmin.storesimpleproduct'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
    Route::post('/superadmin/storeeditproduct/{id}', [SuperadminController::class, 'StoreEditProduct'])
         ->name('superadmin.storeeditproduct');
});

Route::middleware(['auth','role:superadmin'])->group(function() { 
    Route::post('/superadmin/storesimpleeditproduct/{id}/{attid}', [SuperadminController::class, 'StoreSimpleEditProduct'])
         ->name('superadmin.storesimpleeditproduct');
});

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/delete_productlr/{id}',[SuperadminController::class, 'DeleteProductLiteratureReview'])->name('superadmin.delete_productlr'); });


Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::Post('/superadmin/store_review',[SuperadminController::class, 'uploadMultiplePdfs'])->name('superadmin.store_review'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::Post('/superadmin/store_allliterature',[SuperadminController::class, 'uploadAllLiterature'])->name('superadmin.store_allliterature'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/delete_allliterature/{id}',[SuperadminController::class, 'DeleteAllLiterature'])->name('superadmin.delete_allliterature'); });


Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::post('/superadmin/storeproductattribute/{id}',[SuperadminController::class, 'StoreProductAttribute'])->name('superadmin.storeproductattribute'); });


Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/edit_prices_company/{id}',[SuperadminController::class, 'EditPricesCompany'])->name('superadmin.edit_prices_company'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::get('/superadmin/add_product_price/{companyid}/{productid}',[SuperadminController::class, 'AddProductPrice'])->name('superadmin.add_product_price'); });

Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::post('/superadmin/storeproductprice/{companyid}/{productid}',[SuperadminController::class, 'StoreProductPrice'])->name('superadmin.storeproductprice'); });
Route::middleware(['auth','role:superadmin'])->group(function() { 
Route::delete('/superadmin/deleteproductprice/{companyid}/{productid}/{attributeid}',[SuperadminController::class, 'DeleteProductPrice'])->name('superadmin.deleteproductprice'); });

// COMPANYADMIN ROUTE



Route::middleware(['auth','role:companyadmin'])->group(function() { 
Route::get('/companyadmin/dashboard',[CompanyadminController::class, 'CompanyadminDashboard'])->name('companyadmin.dashboard'); });

Route::middleware(['auth','role:companyadmin'])->group(function() { 
Route::get('/companyadmin/logout',[CompanyadminController::class, 'CompanyadminDestroy'])->name('companyadmin.logout'); });

Route::middleware(['auth','role:companyadmin'])->group(function() { 
Route::post('/store/companyprofile',[CompanyadminController::class, 'StoreCompanyProfile'])->name('store.companyprofile'); });

Route::middleware(['auth','role:companyadmin'])->group(function() { 
Route::post('companyadmin/store/company',[CompanyadminController::class, 'StoreCompany'])->name('companyadmin.store.company'); });

Route::middleware(['auth','role:companyadmin'])->group(function() { 
Route::post('companyadmin/store/role',[CompanyadminController::class, 'StoreRole'])->name('companyadmin.store.role'); });


Route::middleware(['auth','role:companyadmin'])->group(function() { 
Route::post('/store/companyuser',[CompanyadminController::class, 'StoreCompanyUser'])->name('store.companyuser'); });

Route::middleware(['auth','role:companyadmin'])->group(function() { 
Route::get('/view_order/{id}', [CompanyadminController::class, 'ViewOrder'])->name('companyadmin.view_order'); });

//yahan se website data shuru ho raha ha


Route::middleware(['auth','role:companyadmin'])->group(function() { 
Route::get('/companyadmin/products',[CompanyadminController::class, 'Products'])->name('companyadmin.products'); });

Route::middleware(['auth','role:companyadmin'])->group(function() { 
Route::get('/companyadmin/product/{id}',[CompanyadminController::class, 'ViewProduct'])->name('companyadmin.product'); });

Route::middleware(['auth','role:companyadmin'])->group(function() { 
Route::get('/companyadmin/sproduct/{id}',[CompanyadminController::class, 'ViewSimpleProduct'])->name('companyadmin.sproduct'); });

Route::middleware(['auth'])->group(function() { 
    Route::get('mycart',[CartController::class, 'MyCart'])->name('cart');
    Route::get('/get-cart-product',[CartController::class, 'GetCartProduct']);
    Route::get('/cart-remove/{rowId}',[CartController::class, 'CartRemove']);
});


Route::middleware(['auth'])->group(function() { 
Route::post('/checkout',[CartController::class, 'Checkout'])->name('checkout'); });

// CART

Route::post('/cart/store/{id}', [CartController::class, 'AddToCart'])->name('cart.store');


// USER 
// SUPERADMIN ROUTE

Route::middleware(['auth','role:user'])->group(function() { 
Route::get('/user/dashboard',[UserController::class, 'UserDashboard'])->name('user.dashboard'); });

Route::middleware(['auth','role:user'])->group(function() { 
Route::get('/user/logout',[UserController::class, 'UserDestroy'])->name('user.logout'); });

Route::middleware(['auth','role:user'])->group(function() { 
Route::post('user/store/company',[UserController::class, 'StoreCompany'])->name('store.company'); });

Route::middleware(['auth','role:user'])->group(function() { 
Route::post('user/store/role',[UserController::class, 'StoreRole'])->name('store.role'); });

Route::middleware(['auth','role:user'])->group(function() { 
Route::get('/user/products',[UserController::class, 'Products'])->name('user.products'); });

Route::middleware(['auth','role:user'])->group(function() { 
Route::get('/user/product/{id}',[UserController::class, 'ViewProduct'])->name('user.product'); });

