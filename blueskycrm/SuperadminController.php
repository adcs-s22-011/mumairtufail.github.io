<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use DB;
use Carbon\Carbon;

use Illuminate\Support\Facades\Schema;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Arr;
use Image;
use Spatie\PdfToImage\Pdf;




class SuperadminController extends Controller
{
    //

    public function SuperadminDashboard(){

    $usercount = DB::table('users')
   ->select('users.id')
   ->where('users.id', '!=' , '1')->get();
    $companies = DB::select('select * from company');
    $orders = DB::table('orders')
                ->join('company', 'orders.company_id', '=', 'company.id')
                ->select('orders.*', 'company.company_name')
                ->get();

    $products = DB::select('select * from products');

        return view('superadmin.index',compact('usercount','orders','companies','products'));



    }


    public function SuperadminLogin(){
        return view('superadmin.superadmin_login');


    } 


    public function SuperadminDestroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/superadmin/login');
    }


    public function SuperadminProfile()
    {

    $id = Auth::user()->id;
    $superadmindata = User::find($id);
    return view('superadmin.superadmin_profile_view',compact('superadmindata'));



    }

 public function StoreProfile(Request $request){

    $id = Auth::user()->id;
    $data = User::find($id);
    $hash = Hash::make($request->password);
    $data->name = $request->name;
    $data->email = $request->email;
    $data->password = $hash;

    if($request->file('profile_picture')){

        $file = $request->file('profile_picture');
        $filename = date('YmdHi').$file->getClientOriginalName();
        $file->move(public_path('uploads/pp'),$filename);
        $data['profile_picture'] = $filename;
    }
    $data->save();
    return redirect()->route('superadmin.profile');


    }

// to create user
    public function CreateUser()
    {
    $companies = DB::select('select * from company');
    return view('superadmin.create_user',compact('companies'));

    }  

// to store created user
     public function StoreUser(Request $request){
    $userr = User::create([ 
        'name' => request()->name, 
        'email' => request()->email, 
        'password' => Hash::make(request()->password), 
        'company_id' => request()->company, 
        'role' => request()->role, 


]);

    $newuser = $userr->id;
    $role = $userr->role;
    $newusercompany = $userr->company_id;
    $data=array('user_id'=>$newuser,"company_id"=>$newusercompany,"role"=>$role);
    DB::table('roles')->insert($data);   


    return redirect()->route('superadmin.create_user');


    }    


    public function ViewUser()
    {
    $users = DB::table('users')
   ->join('roles','users.id','=', 'roles.user_id')
   ->join('company','company.id','=', 'roles.company_id')
   ->select('users.name', 'users.email' , 'roles.role' , 'users.id','company.company_name')
   ->where('users.id', '!=' , '1')->get();

   // $companies = DB::table('company')
   //->join('roles','company.id','=', 'roles.company_id')
   //->select('company.company_name')
   //->where('users.id', '!=' , '1')->get();


    return view('superadmin.view_users',compact('users'));

    }  

    public function DeleteUser(Request $request, $id, $role)
    {

$check = DB::table('roles')->where('user_id', $id)->get();

if ($check->count() > 1) {
    // delete the role with the given role name
    DB::table('roles')->where('user_id', $id)->where('role', $role)->delete();
} else {
    // delete the user and their role
    DB::table('users')->where('id', $id)->delete();
    DB::table('roles')->where('user_id', $id)->where('role', $role)->delete();
}

  return redirect()->back();

    }  



    public function AssignRole()
    {
    $companies = DB::select('select * from company');
    $users = DB::table('users')->where('company_id', '!=' , '0')->get();



    return view('superadmin.assign_role',compact('companies','users'));

    }    

        public function StoreRole(Request $request)
    {
        $user_id = $request->input('user_id');
        $company_id = $request->input('company_id');
        $role = $request->input('role');
        $data=array('user_id'=>$user_id,"company_id"=>$company_id,"role"=>$role);

    $role = DB::table('roles')
   ->where('roles.company_id', '=' , $company_id)
   ->where('roles.user_id', '=' , $user_id)
   ->where('roles.role', '=' , $role)
   ->get();

if (count($role) == 1) {
$notification = array(
    'message' => 'User is already assigned',
    'alert-type' => 'error'
);
} else {
        DB::table('roles')->insert($data);
    $notification = array(
    'message' => 'User assigned',
    'alert-type' => 'success'
);
}   


    return redirect()->route('superadmin.assign_role')->with($notification);

    }    




 // to create user
    public function CreateCompany()
    {
    return view('superadmin.create_company');

    }     


public function StoreCompany(Request $request)
{
    $companyname = $request->input('companyname');
    $shippingaddress = $request->input('shippingaddress');
    $billingaddress = $request->input('billingaddress');
    $email = $request->input('email');
    $data=array('company_name'=>$companyname,"shipping_address"=>$shippingaddress,"billing_address"=>$billingaddress);
    DB::table('company')->insert($data);

    // Get the id of the newly created company
    $company_id = DB::getPdo()->lastInsertId();

    // Retrieve the required data from the attribute_products table
    $attributeProducts = DB::table('attribute_products')
        ->select('id', 'product_id', 'default_price')
        ->get();

    // Iterate over each row and insert into the product_price table
    foreach ($attributeProducts as $attributeProduct) {
        $product_id = $attributeProduct->product_id;
        $attribute_id = $attributeProduct->id;
        $product_price = $attributeProduct->default_price;

        // Insert into the product_price table with the newly created company id
        DB::table('product_price')->insert([
            'company_id' => $company_id,
            'product_id' => $product_id,
            'attribute_id' => $attribute_id,
            'product_price' => $product_price
        ]);
    }

    // Redirect the user to the create company view
    return redirect()->route('superadmin.create_company');
}


public function StoreEdit(Request $request, $id)
{
    $companyname = $request->input('companyname');
    $shippingaddress = $request->input('shippingaddress');
    $billingaddress = $request->input('billingaddress');
 $data = array(
    'company_name' => $companyname,
    'shipping_address' => $shippingaddress,
    'billing_address' => $billingaddress
);

DB::table('company')
    ->where('id', $id)
    ->update($data);



    // Redirect the user to the create company view
  return redirect()->back();
}



    public function ViewCompany()
    {
    $company = DB::select('select * from company');
    return view('superadmin.view_company',compact('company'));

    } 

    public function DeleteCompany($id)
    {
   DB::table('product_price')->where('company_id', $id)->delete();
   DB::table('orders')->where('company_id', $id)->delete();
   DB::table('company')->where('id', $id)->delete();

$usersToDelete = DB::table('roles')
    ->select('user_id')
    ->groupBy('user_id')
    ->havingRaw('COUNT(*) = 1')
    ->where('company_id', $id)
    ->whereNotIn('user_id', function($query) {
        $query->select('user_id')
              ->from('roles')
              ->groupBy('user_id')
              ->havingRaw('COUNT(DISTINCT company_id) > 1');
    })
    ->get();
  foreach ($usersToDelete as $userToDelete) {
    DB::table('users')->where('id', $userToDelete->user_id)->delete();
  }
   DB::table('roles')->where('company_id', $id)->delete();
  // Check if each user_id in the roles table exists in the users table,
  // and update the company_id in the users table if necessary
  $userIds = DB::table('roles')->distinct()->pluck('user_id');
  foreach ($userIds as $userId) {
    $userRole = DB::table('roles')->where('user_id', $userId)->first();
    $user = DB::table('users')->find($userId);
    if ($user && $user->company_id == $id) {
      $otherRoles = DB::table('roles')->where('user_id', $userId)->where('company_id', '!=', $id)->get();
      if ($otherRoles->isNotEmpty()) {
        $newCompanyId = $otherRoles->first()->company_id;
        DB::table('users')->where('id', $userId)->update(['company_id' => $newCompanyId]);
      } else {
        DB::table('users')->where('id', $userId)->delete();
      }
    }
  }

  return redirect()->back();

    }  

    public function EditCompany($id)
    {
    $company = DB::table('company')->where('id', $id)->first();

    return view('superadmin.edit_company',compact('company'));

    }     

    public function CreateCategory(){
    $parent = DB::select('select * from parent_category');   

        return view('superadmin.create_category',compact('parent'));



    }

    public function ManageParentCategory(){
    $parent = DB::select('select * from parent_category');   

        return view('superadmin.create_parentcategory',compact('parent'));



    }


     public function StoreParentCategory(Request $request){
        $categoryname = $request->input('categoryname');
        $data=array('name'=>$categoryname);

    DB::table('parent_category')->insert($data);
    $notification = array(
    'message' => 'Parent Category Created',
    'alert-type' => 'success');       

      return redirect()->back()->with($notification);


    } 

    public function DeleteParentCategory($id){
  
DB::transaction(function () use ($id) {
  // Delete all rows from the category table with parent_id = $id
  DB::table('category')->where('parent_id', $id)->delete();

  // Delete the row from the parent_category table with ID = $id
  DB::table('parent_category')->where('id', $id)->delete();
});

return redirect()->back();


    }

    public function DeleteCategory($id){
  
DB::table('category')->where('id', $id)->delete();

  return redirect()->back();

    }


     public function StoreCategory(Request $request){
        $categoryname = $request->input('categoryname');
        $parent = $request->input('parentcategory');

        $data=array('category_name'=>$categoryname,'parent_id'=>$parent);

    DB::table('category')->insert($data);
    $notification = array(
    'message' => 'Category Created',
    'alert-type' => 'success');       

    return redirect()->route('superadmin.create_category')->with($notification);


    } 

    public function ViewCategory()
    {
$category = DB::table('category')
            ->join('parent_category', 'category.parent_id', '=', 'parent_category.id')
            ->select('category.*', 'parent_category.name as parent_name')
            ->get();
                return view('superadmin.view_category',compact('category'));

    }  

   public function ViewProducts()
    {
    $products = DB::select('select * from products');
    return view('superadmin.view_products',compact('products'));

    } 

   public function AddProduct()
    {
    $category = DB::select('select * from category');   
    $attributes = DB::select('select * from attributes');       
    $literaturereview = DB::select('select * from literaturereview');             

    return view('superadmin.add_product',compact('category','attributes','literaturereview'));

    } 

   public function AddSimpleProduct()
    {
    $category = DB::select('select * from category');   
    $attributes = DB::select('select * from attributes');       
    $literaturereview = DB::select('select * from literaturereview');             

    return view('superadmin.add_simple_product',compact('category','attributes','literaturereview'));

    } 

   public function UploadCsv()
    {        
    return view('superadmin.upload_csv');

    } 

public function ImportCsv(Request $request)
{
    $request->validate([
        'file' => 'required|mimes:csv,txt',
    ]);

    $file = $request->file('file');

    // Create a mapping between category names and IDs
    $categories = DB::table('category')->pluck('id', 'category_name')->toArray();

    $handle = fopen($file, "r");
    $first_row = true; // Flag variable to track the first row
    if ($handle) {
        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            if ($first_row) {
                $first_row = false;
                continue; // Skip the first row
            }

            $category_id = null;

            // Check if the category name exists in the mapping
            if (isset($categories[$data[2]])) {
                $category_id = $categories[$data[2]];
            }

            $product = [
                'product_name' => $data[0],
                'description' => $data[1],
                'category_id' => $category_id,
                'attributes' => $data[3],
                // add more product columns as needed
            ];

            DB::table('products')->insert($product);
        }

        fclose($handle);
    }

    return redirect()->route('superadmin.upload_csv');
}





   public function EditProduct($id)
    {
    $product = DB::table('products')
   ->where('products.id', '=' , $id)
   ->first();
    $attribute = DB::select('select * from attributes');
    $literaturereview = DB::table('product_literaturereview')
              ->where('product_id', '=', $id)
              ->get();
$prod_att = DB::table('attribute_products')
                ->where('product_id', $id)
                ->orderByRaw('sort_id IS NULL, sort_id')
                ->get();

    $category = DB::select('select * from category');   



    return view('superadmin.edit_product',compact('product','attribute','prod_att','category','literaturereview'));

    } 


   public function EditSimpleProduct($id)
    {
    $product = DB::table('products')
   ->where('products.id', '=' , $id)
   ->first();
    $literaturereview = DB::table('product_literaturereview')
              ->where('product_id', '=', $id)
              ->get();
$prod_att = DB::table('attribute_products')
                ->where('product_id', $id)
                ->first();

    $category = DB::select('select * from category');   



    return view('superadmin.edit_simpleproduct',compact('product','prod_att','category','literaturereview'));

    } 


   public function DeleteAttProduct($product_id,$id)
    {

// Delete the row from the product_price table
DB::table('product_price')->where('attribute_id', $id)->delete();

// Delete the row from the attribute_products table
DB::table('attribute_products')->where('id', $id)->delete();


    return redirect()->back();

    } 


   public function DeleteProduct($id)
    {

// delete from products table
DB::table('products')->where('id', $id)->delete();

// delete from attribute_products table if rows exist
if (DB::table('attribute_products')->where('product_id', $id)->exists()) {
    DB::table('attribute_products')->where('product_id', $id)->delete();
}

// delete from product_prices table if rows exist
if (DB::table('product_price')->where('product_id', $id)->exists()) {
    DB::table('product_price')->where('product_id', $id)->delete();
}

    return redirect()->back();

    } 

public function StoreProduct(Request $request)
{
    $productname = $request->input('product_name');
    $description = $request->input('description');
    $categoryid = $request->input('category');
    $attributes = $request->input('attributes');
    $literaturereview = $request->input('literaturereview');

    $data = [
        'product_name' => $productname,
        'description' => $description,
        'category_id' => $categoryid,
        'attributes' => $attributes,
        'literaturereview' => $literaturereview,
    ];

    if ($request->hasFile('product_thumbnail')) {
        $image = $request->file('product_thumbnail');
        $name_gen = hexdec(uniqid()) . '.' . $image->getClientOriginalExtension();

        Image::make($image)->resize(800, 800)->save('uploads/products/thumbnail/' . $name_gen);
        $save_url = 'uploads/products/thumbnail/' . $name_gen;

        $data['product_thumbnail'] = $save_url;
    }

    DB::table('products')->insert($data);

    $notification = [
        'message' => 'Product Successfully Created',
        'alert-type' => 'success',
    ];

    return redirect()->route('superadmin.add_product')->with($notification);
}

public function StoreSimpleProduct(Request $request)
{
$sku = $request->input('sku');
$mpn = $request->input('mpn');
$productname = $request->input('product_name');
$description = $request->input('description');
$categoryid = $request->input('category');
$price = $request->input('price');
$literaturereview = $request->input('literaturereview');

$data = [
    'product_name' => $productname,
    'description' => $description,
    'category_id' => $categoryid,
    'literaturereview' => $literaturereview,
];

if ($request->hasFile('product_thumbnail')) {
    $image = $request->file('product_thumbnail');
    $name_gen = hexdec(uniqid()) . '.' . $image->getClientOriginalExtension();

    Image::make($image)->resize(800, 800)->save('uploads/products/thumbnail/' . $name_gen);
    $save_url = 'uploads/products/thumbnail/' . $name_gen;

    $data['product_thumbnail'] = $save_url;
}

// Insert data into the products table and get the ID of the inserted record
$product_id = DB::table('products')->insertGetId($data);

// Insert data into the attribute_products table
$attribute_data = [
    'sku' => $sku,
    'mpn' => $mpn,
    'variant_thumbnail' => $data['product_thumbnail'],
    'name' => $data['product_name'],
    'default_price' => $price,
    'product_id' => $product_id,
];

$attribute_id = DB::table('attribute_products')->insertGetId($attribute_data);

    $notification = [
        'message' => 'Product Successfully Created',
        'alert-type' => 'success',
    ];


$companies = DB::select('select * from company');
foreach ($companies as $company) { 
    $company_id = $company->id;
    $data = array(
        'product_id' => $product_id,
        'attribute_id' => $attribute_id,
        'product_price' => $price,
        'company_id' => $company_id
    );
    DB::table('product_price')->insert($data);
}

    $notification = [
        'message' => 'Product Successfully Created',
        'alert-type' => 'success',
    ];


    return redirect()->route('superadmin.add_simple_product')->with($notification);
}

public function StoreEditProduct(Request $request, $id)
{
    $productname = $request->input('product_name');
    $description = $request->input('description');
    $categoryid = $request->input('category');
    $attributes = $request->input('attributes');
    $literaturereview = $request->input('literaturereview');

    $data = [
        'product_name' => $productname,
        'description' => $description,
        'category_id' => $categoryid,
        'attributes' => $attributes,
        'literaturereview' => $literaturereview,

    ];

    if ($request->hasFile('product_thumbnail')) {
        $image = $request->file('product_thumbnail');
        $name_gen = hexdec(uniqid()) . '.' . $image->getClientOriginalExtension();

        Image::make($image)->resize(800, 800)->save('uploads/products/thumbnail/' . $name_gen);
        $save_url = 'uploads/products/thumbnail/' . $name_gen;

        $data['product_thumbnail'] = $save_url;
    }

    DB::table('products')->where('id', $id)->update($data);

    $notification = [
        'message' => 'Product Successfully Updated',
        'alert-type' => 'success',
    ];

    return redirect()->back();
}


public function StoreSimpleEditProduct(Request $request, $id, $attid)
{
$sku = $request->input('sku');
$mpn = $request->input('mpn');
$productname = $request->input('product_name');
$description = $request->input('description');
$categoryid = $request->input('category');
$price = $request->input('price');
$literaturereview = $request->input('literaturereview');

$data = [
    'product_name' => $productname,
    'description' => $description,
    'category_id' => $categoryid,
    'literaturereview' => $literaturereview,
];

if ($request->hasFile('product_thumbnail')) {
    $image = $request->file('product_thumbnail');
    $name_gen = hexdec(uniqid()) . '.' . $image->getClientOriginalExtension();

    Image::make($image)->resize(800, 800)->save('uploads/products/thumbnail/' . $name_gen);
    $save_url = 'uploads/products/thumbnail/' . $name_gen;

    $data['product_thumbnail'] = $save_url;
}

// Update data in the products table for the given ID
DB::table('products')->where('id', $id)->update($data);

// Update data in the attribute_products table for the given attribute ID
$attribute_data = [
    'sku' => $sku,
    'mpn' => $mpn,
    'name' => $data['product_name'],
    'default_price' => $price,
];

if (!empty($data['product_thumbnail'])) {
    $attribute_data['variant_thumbnail'] = $data['product_thumbnail'];
}


DB::table('attribute_products')->where('id', $attid)->update($attribute_data);


$companies = DB::select('select * from company');

foreach ($companies as $company) { 
    $company_id = $company->id;

    $data = array(
        'product_price' => $price,
    );

    DB::table('product_price')
        ->where('product_id', $id)
        ->where('attribute_id', $attid)
        ->update($data);
}


$notification = [
    'message' => 'Product Successfully Updated',
    'alert-type' => 'success',
];


    return redirect()->back();
}






public function uploadMultiplePdfs(Request $request)
{
    $lr = $request->input('literature_type');
    $productId = $request->input('product_id');
    $literaturename = $request->input('literature_name');

    if ($request->hasFile('pdf')) {
        $pdf = $request->file('pdf');

            $pdfName = $pdf->getClientOriginalName();
            $unique_id = hexdec(uniqid());
            $name_genn = $unique_id . '.' . $pdf->getClientOriginalExtension();
            $pdf->move(public_path('uploads/literaturereview'), $name_genn);

            $save_url = 'uploads/literaturereview/' . $name_genn;

    if ($request->hasFile('pdfimage')) {
        $image = $request->file('pdfimage');
        $imagegen = $unique_id . '.' . $image->getClientOriginalExtension();
        Image::make($image)->resize(500, 500)->save('uploads/literaturereview/' . $imagegen);

        $image_url = 'uploads/literaturereview/' . $imagegen;

    }


            DB::table('product_literaturereview')->insert([
                'title' => $literaturename,
                'filename' => $unique_id,
                'pdfpath' => $save_url,
                'imagepath' => $image_url,
                'product_id' => $productId,
                'type' => $lr,
            ]);
        }


    return redirect()->back();
}



 public function DeleteProductLiteratureReview($id) {

    DB::table('product_literaturereview')->where('id', $id)->delete();

  return redirect()->back();

}

public function StoreSort(Request $request,$productid,$id)
{
    $sort_order = $request->input('myDropdown');

    DB::table('attribute_products')
        ->where('id', $id)
        ->update(['sort_id' => $sort_order]);
  return redirect()->back();
}

public function StoreAttributeSort(Request $request, $attribute, $id)
{
    $sort_order = $request->input('myDropdown');

    $attribute_value = DB::table('attributes')
        ->where('id', $attribute)
        ->value('attribute_name');

        DB::table($attribute_value)
            ->where('id', $id)
            ->update(['sort_id' => $sort_order]);

    return redirect()->back();
}



public function StoreProductAttribute(Request $request, $id)
{
    $attributes = DB::select('select * from attributes');

    $values = $request->input('values');
    $sku = $request->input('sku');
    $mpn = $request->input('mpn');
    $name = $request->input('name');
    $default_price = $request->input('default_price');
    $product_id = $id;

    $data = array_merge($values, $sku, $mpn, $default_price, $name, ['product_id' => $product_id]);

    if ($request->hasFile('variant_thumbnail')) {
        $image = $request->file('variant_thumbnail');
        $name_gen = hexdec(uniqid()) . '.' . $image->getClientOriginalExtension();

        Image::make($image)->resize(800, 800)->save('uploads/products/thumbnail/' . $name_gen);
        $save_url = 'uploads/products/thumbnail/' . $name_gen;

        $data['variant_thumbnail'] = $save_url;
    }

    $att_id = DB::table('attribute_products')->insertGetId($data);

$default = DB::table('attribute_products')
    ->where('id', $att_id)
    ->value('default_price');

$companies = DB::select('select * from company');
foreach ($companies as $company) { 
    $company_id = $company->id;
    $data = array(
        'product_id' => $id,
        'attribute_id' => $att_id,
        'product_price' => $default,
        'company_id' => $company_id
    );
    DB::table('product_price')->insert($data);
}

    return redirect()->route('superadmin.edit_product', $id);
}



    public function CreateAttributes(){
    $attribute = DB::select('select * from attributes');    
        return view('superadmin.create_attributes',compact('attribute'));


    }

    public function CreateLocation(){
    $locations = DB::select('select * from locations');    
        return view('superadmin.createlocation',compact('locations'));


    }


    public function EditLocation($id){
$location = DB::table('locations')
    ->where('id', $id)
    ->first();        
    return view('superadmin.edit_location',compact('location'));


    }

public function StoreEditLocation(Request $request, $id)
{
    $location_name = $request->input('location_name');
    $phone_number = $request->input('phone_number');
    $latitude = $request->input('latitude');
    $longitude = $request->input('longitude');
    $address = $request->input('address');
    $types = $request->input('type');


    if($request->file('companylogo')){

        $file = $request->file('companylogo');
        $filename = date('YmdHi').$file->getClientOriginalName();
        $file->move(public_path('uploads/companylogos'),$filename);
    }

    if (is_array($types)) {
        $type = implode(',', $types);
    } else {
        // Handle the case when no checkboxes are selected
        $type = '';
    }



    $data = array(
        'location_name' => $location_name,
        'phone_number' => $phone_number,
        'latitude' => $latitude,
        'longitude' => $longitude,
        'address' => $address,
        'type' => $type,
        'logo' => $filename
    );

    // Use the update method instead of insert
    DB::table('locations')
        ->where('id', $id)
        ->update($data);

    return redirect()->route('superadmin.location');
}



public function StoreLocation(Request $request)
{
    $location_name = $request->input('location_name');
    $phone_number = $request->input('phone_number');
    $latitude = $request->input('latitude');
    $longitude = $request->input('longitude');
    $address = $request->input('address');


$types = $request->input('type');
if (is_array($types)) {
    $type = implode(',', $types);
} else {
    // Handle the case when no checkboxes are selected
    $type = '';
}

    if($request->file('companylogo')){

        $file = $request->file('companylogo');
        $filename = date('YmdHi').$file->getClientOriginalName();
        $file->move(public_path('uploads/companylogos'),$filename);
    }

    $data=array('location_name'=>$location_name,"phone_number"=>$phone_number,"latitude"=>$latitude,'longitude'=>$longitude,"address"=>$address,"type"=>$type,"logo"=>$filename);
    DB::table('locations')->insert($data);


    // Redirect the user to the create company view
    return redirect()->route('superadmin.location');
}

public function DeleteLocation($id) {
    // Retrieve the logo filename
    $logoFilename = DB::table('locations')->where('id', $id)->value('logo');
    
    // Delete the logo file
    if (!empty($logoFilename)) {
        $logoPath = public_path('uploads/companylogos/' . $logoFilename);
        if (file_exists($logoPath)) {
            unlink($logoPath);
        }
    }

    // Delete the database record
    DB::table('locations')->where('id', $id)->delete();

    return redirect()->back();
}


    public function CreateLiterature(){
   $literaturereview = DB::select('select * from literaturereview');    
        return view('superadmin.literature_review',compact('literaturereview'));


    }

     public function StoreLiteratureReview(Request $request){

        $literature_review = strtolower($request->input('literature_review'));

        $data=array('name'=>$literature_review);

    DB::table('literaturereview')->insert($data);

      return redirect()->back();

}

public function DeleteLiteratureReview($id) {
    // Retrieve the name of the literature review to be deleted
    $lr = DB::table('literaturereview')->where('id', $id)->first();
    $name = $lr->name;

    // Delete all product_literaturereview rows with matching type
    DB::table('product_literaturereview')->where('type', $name)->delete();

    // Delete the literature review itself
    DB::table('literaturereview')->where('id', $id)->delete();

    return redirect()->back();
}


 public function DeleteAttribute($id) {
    // Get the attribute name for the given $id
    $attribute = DB::table('attributes')->select('attribute_name')->where('id', $id)->first();
    $attributeName = $attribute->attribute_name;

if (Schema::hasColumn('attribute_products', $attributeName)) {
    Schema::table('attribute_products', function ($table) use ($attributeName) {
        $table->dropColumn($attributeName);
    });
}
if (Schema::hasTable($attributeName)) {
    // drop the table if it exists
    Schema::dropIfExists($attributeName);
}

DB::table('products')
  ->whereRaw("FIND_IN_SET('$attributeName', attributes)")
  ->update([
    'attributes' => DB::raw("TRIM(BOTH ',' FROM REPLACE(CONCAT(',', attributes, ','), ',$attributeName,', ','))"),
  ]);

    // Delete the attribute from attributes table
    DB::table('attributes')->where('id', $id)->delete();

  return redirect()->back();

}

     public function StoreAttribute(Request $request){

$attribute_name = strtoupper($request->input('attribute_name'));

        $data=array('attribute_name'=>$attribute_name);

    DB::table('attributes')->insert($data);


Schema::table('attribute_products', function (Blueprint $table) use ($attribute_name) {
    $table->string($attribute_name)->nullable();
});


Schema::create($attribute_name, function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->integer('sort_id')->default(0);
    $table->integer('attribute_id');
    $table->text('attribute_value');
});


    $notification = array(
    'message' => 'Attribute Successfully Created',
    'alert-type' => 'success');    

    return redirect()->route('superadmin.create_attributes')->with($notification);


    }

   public function EditAttribute($id)
    {
    $attirbutes = DB::table('attributes')
   ->where('id', '=' , $id)
   ->first();

   $specificid = $attirbutes->attribute_name;
    $attirbute_values = DB::table($specificid)
   ->where('attribute_id', '=' , $id)
   ->get();

    return view('superadmin.edit_attribute',compact('attirbutes','attirbute_values'));

    }

   public function EditAttributeValue($attribute, $id)
    {
// Get the attribute name from the `attributes` table
$attributeName = DB::table('attributes')
    ->where('id', $attribute)
    ->value('attribute_name');
$attributeValue = DB::table($attributeName)
    ->where('id', $id)
    ->value('attribute_value');



return view('superadmin.edit_attributevalue', compact('attributeValue','attribute','id'));



    }

     public function StoreAttributeValues(Request $request ,$id){

    $attirbutes = DB::table('attributes')
   ->where('id', '=' , $request->route('id'))
   ->first();

   $tablename = $attirbutes->attribute_name;

        $attribute_value = $request->input('attribute_value');
        $attribute_id = $attirbutes->id;

        $data=array('attribute_value'=>$attribute_value,'attribute_id'=>$attribute_id);

    DB::table($tablename)->insert($data);
    $notification = array(  
    'message' => 'Value Successfully Created',
    'alert-type' => 'success');       


    return redirect()->route('superadmin.edit_attribute',$request->route('id'))->with($notification);


    }


     public function StoreEditAttributeValues(Request $request ,$attribute,$id){

// Get the attribute name from the `attributes` table
$attributeName = DB::table('attributes')
    ->where('id', $attribute)
    ->value('attribute_name');

$att_value = $request->input('attribute_value');

// Update the attribute_value column in the appropriate table
DB::table($attributeName)
    ->where('id', $id)
    ->update(['attribute_value' => $att_value]);

    return redirect()->route('superadmin.closepopup');



    }

   public function closepopup()
    {



return view('superadmin.closepopup');



    }
public function DeleteAttributeValue($attribute, $id)
{
    // Get the attribute name from the `attributes` table
    $attributeName = DB::table('attributes')
        ->where('id', $attribute)
        ->value('attribute_name');

    
    // Delete the row from the appropriate table
    DB::table($attributeName)->where('id', $id)->delete();
    
    return redirect()->back();
}


   public function EditPricesCompany($id)
    {
    $products = DB::select('select * from products');
    $company = DB::table('company')
   ->where('id', '=' , $id)
   ->first();
    $attribute = DB::select('select * from attributes');



    return view('superadmin.edit_prices_company',compact('products','attribute','company'));

    }   
    
   public function AddProductPrice($companyid,$productid)
    {
    $product = DB::table('products')
   ->where('id', '=' , $productid)
   ->first();    
   $company = DB::table('company')
   ->where('id', '=' , $companyid)
   ->first();

    $combine = DB::table('attribute_products')
   ->join('product_price','attribute_products.id','=', 'product_price.attribute_id')
   ->select('attribute_products.*', 'product_price.*')
   ->where('attribute_products.product_id', '=' , $productid)->get();

    $attribute = DB::select('select * from attributes');
    $prod_att = DB::select('SELECT * FROM attribute_products WHERE product_id = ?', [$productid]);




    return view('superadmin.add_product_price',compact('product','attribute','company','prod_att','combine'));

    } 

     public function StoreProductPrice(Request $request ,$companyid,$productid){

    $product = DB::table('products')
   ->where('id', '=' , $productid)
   ->first();    
   $company = DB::table('company')
   ->where('id', '=' , $companyid)
   ->first();

        $product_id = $request->input('product_id');
        $attribute_id = $request->input('attribute_id');
        $company_id = $request->input('company_id');
        $product_price = $request->input('product_price');

        $data=array('product_id'=>$product_id,'attribute_id'=>$attribute_id,'product_price'=>$product_price,'company_id'=>$company_id);

    DB::table('product_price')->insert($data);
    $notification = array(  
    'message' => 'Price Successfully Added',
    'alert-type' => 'success');       


return redirect()->route('superadmin.add_product_price', [
    'companyid' => $request->route('companyid'),
    'productid' => $request->route('productid')
])->with($notification);

    }

     public function DeleteProductPrice(Request $request ,$companyid,$productid,$attributeid){

    $product = DB::table('products')
   ->where('id', '=' , $productid)
   ->first();    
   $company = DB::table('company')
   ->where('id', '=' , $companyid)
   ->first();

DB::table('product_price')
    ->where('id', '=', $attributeid)
    ->delete();

    $notification = array(  
    'message' => 'Price Successfully Deleted',
    'alert-type' => 'success');       


return redirect()->route('superadmin.add_product_price', [
    'companyid' => $request->route('companyid'),
    'productid' => $request->route('productid')
])->with($notification);

    }

public function ManageOrders(){
    $orders = DB::table('orders')
                ->join('company', 'orders.company_id', '=', 'company.id')
                ->select('orders.*', 'company.company_name')
                ->get();
    return view('superadmin.manage_orders', compact('orders'));
}


  
public function CreateOrders() {
    $companies = DB::select('select * from company');
    $products = DB::select('select * from products'); // Assuming you have a "products" table
    $attribute_products = DB::select('select * from attribute_products');

    return view('superadmin.create_orders', compact('companies','products', 'attribute_products'));
}

  public function fetchDefaultPrice($companyId, $productId)
{
    $defaultPrice = DB::table('attribute_products')
        ->where('company_id', $companyId)
        ->where('product_id', $productId)
        ->value('default_price');

    return response()->json(['default_price' => $defaultPrice]);
}

public function fetchAttributesByProduct($productId)
{
    $attributeProducts = DB::table('attribute_products')
        ->where('product_id', $productId)
        ->get();

    return response()->json($attributeProducts);
}


public function getProductAttributes($productId) {
    $attributeProducts = DB::table('attribute_products')
        ->where('product_id', $productId)
        ->get();

    return response()->json($attributeProducts);
}



   public function ViewOrder($id)
    {

$orderDetails = DB::table('order_details')
                ->where('order_id', $id)
                ->get();

$mainorder = DB::table('orders')
                ->where('id', $id)
                ->first();




    return view('superadmin.view_order',compact('orderDetails','mainorder'));

    } 





public function StoreStatus(Request $request, $id){
    $status = $request->input('status');
    $data = array('status' => $status);
    DB::table('orders')->where('id', $id)->update($data);

    return redirect()->route('superadmin.manage_orders');
}
public function updateAttribute(Request $request)
{
    $oldName = $request->input('oldName');
    $newName = $request->input('newName');

    // Perform any necessary validation and error handling

    // Get all table names from the database
    $database = env('DB_DATABASE');
    $tables = DB::select("SHOW TABLES FROM $database");

    foreach ($tables as $table) {
        $tableName = reset($table);
        if ($tableName === $oldName) {
            // Rename the table to the new name
            Schema::rename($oldName, $newName);
        }
    }

    // Update attribute names in the "attributes" table
    DB::table('attributes')->where('attribute_name', $oldName)->update(['attribute_name' => $newName]);

    return response()->json(['success' => true, 'message' => 'Table name and attribute name updated successfully']);
}


   public function AllLiterature()
    {


    $literaturereview = DB::select('select * from literaturereview');             
    $products = DB::select('select * from products');
    $types = DB::table('product_literaturereview')
        ->select('type')
        ->distinct()
        ->pluck('type');
    $plr = DB::select('select * from product_literaturereview');             

    return view('superadmin.all_literature',compact('literaturereview','products','types','plr'));

    } 


public function uploadAllLiterature(Request $request)
{
    $literaturename = $request->input('literature_name');
    $productId = $request->input('dropdown_value');
    $type = $request->input('type');
    $customType = $request->input('custom_type');

    // Determine product_id and public values based on the $productId
    $product_id = ($productId === 'universal') ? 0 : $productId;
    $public = ($productId === 'universal') ? 1 : 0;

    if ($request->hasFile('pdf')) {
        $pdf = $request->file('pdf');
        $pdfName = $pdf->getClientOriginalName();
        $unique_id = hexdec(uniqid());
        $name_genn = $unique_id . '.' . $pdf->getClientOriginalExtension();
        $pdf->move(public_path('uploads/literaturereview'), $name_genn);
        $save_url = 'uploads/literaturereview/' . $name_genn;

        $image_url = null; // Set image_url to null by default

        if ($request->hasFile('pdfimage')) {
            $image = $request->file('pdfimage');
            $imagegen = $unique_id . '.' . $image->getClientOriginalExtension();
            Image::make($image)->resize(500, 500)->save('uploads/literaturereview/' . $imagegen);
            $image_url = 'uploads/literaturereview/' . $imagegen;
        }

        // Determine the type value based on $type or $customType
        $selected_type = ($type === 'custom') ? $customType : $type;

        // Check if the type already exists in the 'literaturereview' column of 'products' table
        if ($product_id !== 'universal') {
            $product = DB::table('products')->where('id', $product_id)->first();
            if ($product) {
                $current_literature_review = $product->literaturereview;
                $types_array = explode(',', $current_literature_review);

                if (!in_array($selected_type, $types_array)) {
                    $updated_literature_review = $current_literature_review ? $current_literature_review . ',' . $selected_type : $selected_type;

                    DB::table('products')->where('id', $product_id)->update([
                        'literaturereview' => $updated_literature_review,
                    ]);
                }
            }
        }

        DB::table('product_literaturereview')->insert([
            'title' => $literaturename,
            'filename' => $unique_id,
            'pdfpath' => $save_url,
            'imagepath' => $image_url,
            'product_id' => $product_id,
            'public' => $public,
            'type' => $selected_type,
        ]);
    }

    return redirect()->back();
}


public function DeleteAllLiterature($id)
{
    // Get the literature review entry from the 'product_literaturereview' table
    $literatureReview = DB::table('product_literaturereview')->where('id', $id)->first();

    if (!$literatureReview) {
        // If the literature review entry with the given $id doesn't exist, redirect back or handle the error.
        return redirect()->back()->with('error', 'Literature Review not found.');
    }

    // Delete the files from public/uploads/literaturereview
    $pdfFilePath = public_path($literatureReview->pdfpath);
    if ($pdfFilePath && file_exists($pdfFilePath)) {
        unlink($pdfFilePath);
    }

    $imageFilePath = public_path($literatureReview->imagepath);
    if ($imageFilePath && file_exists($imageFilePath)) {
        unlink($imageFilePath);
    }

    // Delete the literature review entry from the 'product_literaturereview' table
    DB::table('product_literaturereview')->where('id', $id)->delete();

    // Redirect back with success message or handle the success message as needed
    return redirect()->back()->with('success', 'Literature Review deleted successfully.');
}

public function EditProductAttribute(Request $request, $id)
    {
        $product_attribute = DB::table('attribute_products')->find($id);

        if (!$product_attribute) {
            // Handle the case where the product attribute with the given ID is not found
            return redirect()->back()->with('error', 'Product attribute not found.');
        }

        // Fetch the request inputs for the fields you want to update
        $mpn = $request->input('mpn');
         $sku = $request->input('sku');
        $name = $request->input('name');
        $default_price = $request->input('default_price');

        // Update the fields in the database directly
        DB::table('attribute_products')
            ->where('id', $id)
            ->update([
                'mpn' => $mpn,
                 'sku' => $sku,
                'name' => $name,
                'default_price' => $default_price,
            ]);


  // Update product_price for each company where attribute_id is equal to $id
    DB::table('product_price')
        ->where('attribute_id', $id)
        ->update(['product_price' => $default_price]);



        return redirect()->route('superadmin.edit_product', $product_attribute->product_id)
            ->with('success', 'Product attribute updated successfully.');
              

    }






}
