<?php 
/**
 * Plugin Name: Export Woo Order To XML
 * Plugin URI: https://modestbyte.com/
 * Description: Export WooCommerce Oders to XML.
 * Version: 1.6
 * Author: Dwayne Parton
 * Author URI: https://dwayneparton.com
 * Requires at least: 4.4
 * Tested up to: 4.7
 *
 * Text Domain: export-woocommerce-order-to-xml
 * Domain Path: 
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'EWO__PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'EWO__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

//hook into woocommerce create order.   
add_action('woocommerce_thankyou', 'createOrderXML', 10, 1);
//update if order updated
add_action('woocommerce_process_shop_order_meta', 'createOrderXML', 10, 1);

//Creates and Updates Order XML
function createOrderXML($order_id){
	//Write the XML File
	buildOrderXML($order_id);
}


function buildOrderXML($order_id){
	//Define the XML Header
	$header = '<?xml version = "1.0" encoding="Windows-1252" standalone="yes"?>
<VFPData>
	<xsd:schema id="VFPData" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:msdata="urn:schemas-microsoft-com:xml-msdata">
		<xsd:element name="VFPData" msdata:IsDataSet="true">
			<xsd:complexType>
				<xsd:choice maxOccurs="unbounded">
					<xsd:element name="ordtable" minOccurs="0" maxOccurs="unbounded">
						<xsd:complexType>
							<xsd:sequence>
								<xsd:element name="orderid">
									<xsd:simpleType>
										<xsd:restriction base="xsd:string">
											<xsd:maxLength value="20"/>
										</xsd:restriction>
									</xsd:simpleType>
								</xsd:element>
								<xsd:element name="custid">
									<xsd:simpleType>
										<xsd:restriction base="xsd:string">
											<xsd:maxLength value="50"/>
										</xsd:restriction>
									</xsd:simpleType>
								</xsd:element>
								<xsd:element name="orddate" type="xsd:date"/>
								<xsd:element name="sku">
									<xsd:simpleType>
										<xsd:restriction base="xsd:string">
											<xsd:maxLength value="15"/>
										</xsd:restriction>
									</xsd:simpleType>
								</xsd:element>
								<xsd:element name="wholename">
									<xsd:simpleType>
										<xsd:restriction base="xsd:string">
											<xsd:maxLength value="100"/>
										</xsd:restriction>
									</xsd:simpleType>
								</xsd:element>
								<xsd:element name="wholeid">
									<xsd:simpleType>
										<xsd:restriction base="xsd:string">
											<xsd:maxLength value="75"/>
										</xsd:restriction>
									</xsd:simpleType>
								</xsd:element>
								<xsd:element name="qty">
									<xsd:simpleType>
										<xsd:restriction base="xsd:decimal">
											<xsd:totalDigits value="4"/>
											<xsd:fractionDigits value="0"/>
										</xsd:restriction>
									</xsd:simpleType>
								</xsd:element>
								<xsd:element name="pack">
									<xsd:simpleType>
										<xsd:restriction base="xsd:decimal">
											<xsd:totalDigits value="10"/>
											<xsd:fractionDigits value="0"/>
										</xsd:restriction>
									</xsd:simpleType>
								</xsd:element>
								<xsd:element name="descript">
									<xsd:simpleType>
										<xsd:restriction base="xsd:string">
											<xsd:maxLength value="100"/>
										</xsd:restriction>
									</xsd:simpleType>
								</xsd:element>
								<xsd:element name="price">
									<xsd:simpleType>
										<xsd:restriction base="xsd:decimal">
											<xsd:totalDigits value="13"/>
											<xsd:fractionDigits value="4"/>
										</xsd:restriction>
									</xsd:simpleType>
								</xsd:element>
							</xsd:sequence>
						</xsd:complexType>
					</xsd:element>
				</xsd:choice>
				<xsd:anyAttribute namespace="http://www.w3.org/XML/1998/namespace" processContents="lax"/>
			</xsd:complexType>
		</xsd:element>
	</xsd:schema>
';
	$ordtable = '';
	$footer = '</VFPData>';		
	$order = wc_get_order( $order_id );
	// Get the custumer ID
	$customer_id = $order->get_user_id();
	//While we have the customer id, go ahead and create/update the customer xml
	createCustomerXML($customer_id);
	//Get the order data.
	$order_data = $order->get_data();
	## Creation and modified WC_DateTime Object date string ##
	// Using a formated date ( with php date() function as method)
	$order_date_created = $order_data['date_created']->date('Y-m-d');
	$order_date_modified = $order_data['date_modified']->date('Y-m-d');
	// Using a timestamp ( with php getTimestamp() function as method)
	$order_timestamp_created = $order_data['date_created']->getTimestamp();
	$order_timestamp_modified = $order_data['date_modified']->getTimestamp();

	// Iterating through each WC_Order_Item objects
	foreach( $order-> get_items() as $item_key => $item_values ):

	    ## Using WC_Order_Item methods ##

	    // Item ID is directly accessible from the $item_key in the foreach loop or
	    $item_id = $item_values->get_id();

	    $item_name = $item_values->get_name(); // Name of the product
	    $item_type = $item_values->get_type(); // Type of the order item ("line_item")

	    ## Access Order Items data properties (in an array of values) ##
	    $item_data = $item_values->get_data();

	    $product_name = $item_data['name'];
	    $product_id = $item_data['product_id'];
	    $variation_id = $item_data['variation_id'];
	    $quantity = $item_data['quantity'];
	    $tax_class = $item_data['tax_class'];
	    $line_subtotal = $item_data['subtotal'];
	    $line_subtotal_tax = $item_data['subtotal_tax'];
	    $line_total = $item_data['total'];
	    $line_total_tax = $item_data['total_tax'];
	    // Check if product has variation.
		if ($variation_id) { 
			$product = new WC_Product($variation_id);
		} else {
			$product = new WC_Product($product_id);
		}
		// Get SKU
		$sku = $product->get_sku();

	    $ordtable .= '<ordtable>';
	    $ordtable .= '<orderid>'.$order_id.'</orderid>';
		$ordtable .= '<custid>'.$customer_id.'</custid>';
		$ordtable .= '<orddate>'.$order_date_created.'</orddate>';
		$ordtable .= '<sku>'.$sku.'</sku>';
		$ordtable .= '<wholename/>';
		$ordtable .= '<wholeid/>';
		$ordtable .= '<qty>'.$quantity.'</qty>';
		$ordtable .= '<pack></pack>';
		$ordtable .= '<descript>'.$product_name.'</descript>';
		$ordtable .= '<price>'.$line_total.'</price>';
	    $ordtable .= '</ordtable>';

	endforeach;

	// Build your file contents as a string
	$file_contents = $header.$ordtable.$footer;
	// Open or create a file (this does it in the same dir as the script)
	// This is where you name the file
	$order = fopen(EWO__PLUGIN_DIR.'exports/ord'.$order_id.'.xml', 'w') or die("can't open file");
	// Write the string's contents into that file
	fwrite($order, $file_contents);
	// Close 'er up
	fclose($order);
}

//Creates & Updated Customer XML File
function createCustomerXML($customer_id){
    //if file does not exist. 
	buildCustomerXML($customer_id);
}

function buildCustomerXML($customer_id){
	$header = '<?xml version = "1.0" encoding="Windows-1252" standalone="yes"?>
<VFPData>
	<xsd:schema id="VFPData" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:msdata="urn:schemas-microsoft-com:xml-msdata">
		<xsd:element name="VFPData" msdata:IsDataSet="true">
			<xsd:complexType>
				<xsd:choice maxOccurs="unbounded">
					<xsd:element name="custable" minOccurs="0" maxOccurs="unbounded">
						<xsd:complexType>
							<xsd:sequence>
								<xsd:element name="firstname">
									<xsd:simpleType>
										<xsd:restriction base="xsd:string">
											<xsd:maxLength value="30"/>
										</xsd:restriction>
									</xsd:simpleType>
								</xsd:element>
								<xsd:element name="lastname">
									<xsd:simpleType>
										<xsd:restriction base="xsd:string">
											<xsd:maxLength value="30"/>
										</xsd:restriction>
									</xsd:simpleType>
								</xsd:element>
								<xsd:element name="custid">
									<xsd:simpleType>
										<xsd:restriction base="xsd:string">
											<xsd:maxLength value="50"/>
										</xsd:restriction>
									</xsd:simpleType>
								</xsd:element>
								<xsd:element name="email">
									<xsd:simpleType>
										<xsd:restriction base="xsd:string">
											<xsd:maxLength value="75"/>
										</xsd:restriction>
									</xsd:simpleType>
								</xsd:element>
								<xsd:element name="company">
									<xsd:simpleType>
										<xsd:restriction base="xsd:string">
											<xsd:maxLength value="50"/>
										</xsd:restriction>
									</xsd:simpleType>
								</xsd:element>
								<xsd:element name="address">
									<xsd:simpleType>
										<xsd:restriction base="xsd:string">
											<xsd:maxLength value="50"/>
										</xsd:restriction>
									</xsd:simpleType>
								</xsd:element>
								<xsd:element name="city">
									<xsd:simpleType>
										<xsd:restriction base="xsd:string">
											<xsd:maxLength value="30"/>
										</xsd:restriction>
									</xsd:simpleType>
								</xsd:element>
								<xsd:element name="state">
									<xsd:simpleType>
										<xsd:restriction base="xsd:string">
											<xsd:maxLength value="30"/>
										</xsd:restriction>
									</xsd:simpleType>
								</xsd:element>
								<xsd:element name="country">
									<xsd:simpleType>
										<xsd:restriction base="xsd:string">
											<xsd:maxLength value="30"/>
										</xsd:restriction>
									</xsd:simpleType>
								</xsd:element>
								<xsd:element name="zip">
									<xsd:simpleType>
										<xsd:restriction base="xsd:string">
											<xsd:maxLength value="5"/>
										</xsd:restriction>
									</xsd:simpleType>
								</xsd:element>
								<xsd:element name="phone">
									<xsd:simpleType>
										<xsd:restriction base="xsd:string">
											<xsd:maxLength value="15"/>
										</xsd:restriction>
									</xsd:simpleType>
								</xsd:element>
							</xsd:sequence>
						</xsd:complexType>
					</xsd:element>
				</xsd:choice>
				<xsd:anyAttribute namespace="http://www.w3.org/XML/1998/namespace" processContents="lax"/>
			</xsd:complexType>
		</xsd:element>
	</xsd:schema>
';
	$custable = '';
	$footer = '</VFPData>';		

	//Customer Data
	$customer = get_userdata( $customer_id );
    $first_name = $customer->first_name;
    $last_name = $customer->last_name;
    $email = $customer->billing_company;
    $company = $customer->billing_address_1;
    $city = $customer->billing_city;
    $state = $customer->billing_state;
    $zip = $customer->billing_postcode;
    $country = $customer->billing__country;
    $phone = $customer->billing_phone;

    //Build the Custable
	$custable .= '<custable>';
	$custable .= '<firstname>'.$first_name.'</firstname>';
	$custable .= '<lastname>'.$last_name.'</lastname>';
	$custable .= '<custid>'.$customer_id.'</custid>';
	$custable .= '<email>'.$email.'</email>';
	$custable .= '<company>'.$company.'</company>';
	$custable .= '<address>'.$customer_id.'</address>';
	$custable .= '<city>'.$city.'</city>';
	$custable .= '<state>'.$state.'</state>';
	$custable .= '<country>'.$country.'</country>';
	$custable .= '<zip>'.$postcode.'</zip>';
	$custable .= '<phone>'.$phone.'</phone>';
	$custable .= '</custable>';

	// Build your file contents as a string
	$file_contents = $header.$custable.$footer;
	// Open or create a file (this does it in the same dir as the script)
	// This is where you name the file
	$customer = fopen(EWO__PLUGIN_DIR.'exports/cust'.$customer_id.'.xml', 'w') or die("can't open file");
	// Write the string's contents into that file
	fwrite($customer, $file_contents);
	// Close 'er up
	fclose($customer);
}

?>