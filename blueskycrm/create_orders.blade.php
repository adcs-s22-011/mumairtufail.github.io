	@extends('superadmin.superadmin_dashboard')
@section('superadmin')

<!-- Include Bootstrap CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">

<!-- Add custom CSS styles -->
<style>
    .form-group {
        margin-bottom: 15px;
    }
    label {
        display: inline-block;
        margin-bottom: 5px;
    }
    .product-dropdown, .quantity-input, .remove-product {
        display: inline-block;
        margin-right: 5px;
    }
</style>

<div class="page-content">
    <h2>Create New Order</h2>
    
    <form method="post" class="form-horizontal">
        @csrf
        <div class="form-group">
    <label for="order_number" class="col-sm-2 control-label">Order Number:</label>
    <div class="col-sm-10">
        <input type="text" name="order_number" id="order_number" readonly class="form-control">
    </div>
</div>

        <div class="form-group">
            <label for="order_date" class="col-sm-2 control-label">Order Date:</label>
            <div class="col-sm-10">
                <input type="date" name="order_date" value="{{ date('Y-m-d') }}" required class="form-control">
            </div>
        </div>

        <div class="form-group">
            <label for="project_name" class="col-sm-2 control-label">Project Name:</label>
            <div class="col-sm-10">
                <input type="text" name="project_name" required class="form-control">
            </div>
        </div>

        <div class="form-group">
            <label for="purchase_order" class="col-sm-2 control-label">Purchase Order:</label>
            <div class="col-sm-10">
                <input type="text" name="purchase_order" required class="form-control">
            </div>
        </div>

        <div class="form-group">
            <label for="approved_by" class="col-sm-2 control-label">Approved By:</label>
            <div class="col-sm-10">
                <input type="text" name="approved_by" required class="form-control">
            </div>
        </div>

   <div class="form-group">
    <label for="company_id" class="col-sm-2 control-label">Company:</label>
    <div class="col-sm-10">
        <select name="company_id" required class="form-control">
            <option value="" disabled selected>Select a Company</option>
            <!-- Loop through companies -->
            @foreach ($companies as $company)
                <option value="{{ $company->id }}">{{ $company->company_name }}</option>
            @endforeach
        </select>
    </div>
</div>
<div class="form-group">
    <label for="status" class="col-sm-2 control-label">Status:</label>
    <div class="col-sm-10">
        <input type="text" name="status" class="form-control" value="pending" readonly>
    </div>
</div>


<div id="product_list" class="form-group">
    <label for="product" class="col-sm-2 control-label">Products:</label>
    <div class="col-sm-10">
        <div class="product-item">

<select name="product" id="product" class="product-dropdown form-control" required style="width:auto; display:inline-block;">
    <option value="" disabled selected>Select a Product</option>
    @foreach ($products as $product)
        <option value="{{ $product->id }}">{{ $product->product_name }}</option>
    @endforeach
</select>

<!-- Second dropdown (initially disabled) -->
<select name="productAttribute" id="productAttribute" class="product-dropdown form-control" required style="width:auto; display:inline-block;" disabled>
    <option value="" disabled selected>Select a Product Attribute</option>
</select>

    <input type="number" name="quantities[]" class="quantity-input form-control" value="1" min="1" style='width:auto; display:inline-block;'>
            <button type="button" class="remove-product btn btn-danger">Remove</button><br>
        </div>
    </div>
</div>
<button type="button" id="add_product" class="btn btn-primary">Add Product</button><br><br>

<button type="submit" class="btn btn-success">Submit Order</button>
</form>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const addButton = document.getElementById('add_product');
    const productList = document.getElementById('product_list');

    addButton.addEventListener('click', function() {
        const newProductItem = document.createElement('div');
        newProductItem.className = 'product-item';

        const newProduct = document.getElementById('product').cloneNode(true);
        const newProductAttribute = document.getElementById('productAttribute').cloneNode(true);
        const newQuantity = document.querySelector('.quantity-input').cloneNode(true);
        const removeButton = document.createElement('button');
        removeButton.textContent = 'Remove';
        removeButton.className = 'remove-product btn btn-danger';

        newProduct.selectedIndex = 0;
        newProductAttribute.selectedIndex = 0;
        newQuantity.value = 1;

        newProduct.addEventListener('change', function() {
            newQuantity.value = 1; // Reset quantity
        });

        removeButton.addEventListener('click', function() {
            newProductItem.remove();
        });

        newProductItem.appendChild(newProduct);
        newProductItem.appendChild(newProductAttribute);
        newProductItem.appendChild(newQuantity);
        newProductItem.appendChild(removeButton);
        productList.querySelector('.col-sm-10').appendChild(newProductItem);
    });

    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-product')) {
            e.target.parentNode.remove();
        }
    });

    // When the product selection changes
    document.addEventListener('change', function(e) {
        if (e.target && e.target.classList.contains('product-dropdown')) {
            const productId = e.target.value; // Selected product ID
            const productAttributeDropdown = e.target.nextElementSibling;

            // Enable and clear the second dropdown
            productAttributeDropdown.disabled = false;
            productAttributeDropdown.innerHTML = '<option value="" disabled selected>Select a Product Attribute</option>';

            // Fetch and populate the second dropdown based on the selected product
            fetch(`/fetch-attributes-by-product/${productId}`) // Replace with the actual endpoint
                .then(response => response.json())
                .then(attributes => {
                    attributes.forEach(attribute => {
                        const option = document.createElement('option');
                        option.value = attribute.id;
                        option.textContent = attribute.name;
                        productAttributeDropdown.appendChild(option);
                    });
                });
        }
    });
});
</script>

<script>
	  // Generate a random and unique order number
    function generateOrderNumber() {
        const timestamp = Date.now();
        const randomPart = Math.floor(Math.random() * 10000); // You can adjust the random range as needed
        return `ORD${timestamp}${randomPart}`;
    }

    // Set the initial order number
    const orderNumberInput = document.getElementById('order_number');
    orderNumberInput.value = generateOrderNumber();

</script>



<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>

<style>
    .product-item {
        margin-bottom: 10px;
    }
    .product-item:last-child {
        margin-bottom: 0;
    }
</style>



@endsection
