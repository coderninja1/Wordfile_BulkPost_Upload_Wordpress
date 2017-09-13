<?php
require_once "lib/Dropbox/autoload.php";
include('./doc.php');
require_once '../../../wp-load.php';
require_once ('../../../wp-admin/includes/taxonomy.php');
require_once('../../../wp-admin/includes/image.php');
require_once "./class.DOCX-HTML.php";
require_once("dUnzip2.inc.php");

if (isset($_GET['folder_path'])) {
	 $folder_path = $_GET['folder_path'];
} 
use \Dropbox as dbx;
$appInfo = dbx\AppInfo::loadFromJsonFile("app-info.json");
$webAuth = new dbx\WebAuthNoRedirect($appInfo, "PHP-Example/1.0");
$authorizeUrl = $webAuth->start();
$dbxClient = new dbx\Client($accessToken, "PHP-Example/1.0");
$accountInfo = $dbxClient->getAccountInfo();
$folderMetadata = $dbxClient->getMetadataWithChildren("/".$folder_path);

if (!file_exists($folder_path)) {
	mkdir($folder_path, 0777, true);
}
$publish_time_interval = 0;
foreach ($folderMetadata as $key => $value1){
	ob_start();
	foreach ($value1 as $key => $data){
		
		$path= $data['path'];
		$path1 = substr($path, 1);
		$f = fopen($path1, "w+b");
		$fileMetadata = $dbxClient->getFile($path, $f);
		fclose($f);
		$data=$dbxClient->delete($path);
		if($path1 != '..')
		{
			$file_name=$path1;
			$objdb = new DocxImages($file_name);
			$tags = new DOMDocument();
			$objdb->setFile($file_name);
			global $wpdb;
			$table_name = $wpdb->prefix . "file_status";
			$rows = $wpdb->get_results("SELECT * FROM $table_name where file_name LIKE '$file_name%'");
			foreach ($rows as $row){
				$file_exist=$row->file_name;
				$upload_status=$row->upload_status;
			}
			if($file_exist===$file_name && $upload_status==='1'){
				//echo $file_name." "."file is already Uploaded";
			}
			else{
				
				$arrData=array(
					'file_name'=>$file_name,
					'upload_status'=>1
				);
				global $wpdb;
				$table_name = $wpdb->prefix . "file_status";
				$wpdb->insert(
						$table_name, //table
						array('file_name' => $file_name, 'upload_status'=>1), //data
						array('%s', '%s') //data format			
				);
				$image_path = $objdb->displayImages();
				if(!$objdb->get_errors()){
					$html = $objdb->to_html();
					$html1 = $objdb->to_html1();
					$plain_text = $objdb->to_plain_text();
					$tags->loadHTML($html);
				} 
				else{
					//echo implode(', ',$objdb->get_errors());
				}
				$post_title = "";
				$post_category = "";
				$post_tags = "";
				$post_author = "";
				$post_content1 = "";
				$post_data = "";
				foreach($tags->getElementsByTagName('h1') as $element){
					$post_title .=  $element->nodeValue;
				}
				foreach($tags->getElementsByTagName('h2') as $element){
					$post_category .= $element->nodeValue;
				}
				foreach($tags->getElementsByTagName('h3') as $element){
					$post_tags .= $element->nodeValue;
				}
				foreach($tags->getElementsByTagName('h4') as $element){
					$post_author .=  $element->nodeValue;
				}
				
				$extract = new DOCXtoHTML();
				$extract->docxPath = $file_name;
				$extract->Init();
				$post_data = $extract->output;
				$post_data=  mb_convert_encoding($post_data, "HTML-ENTITIES", "UTF-8");
				$html_p = new DOMDocument();
				$html_p->loadHTML($post_data);
				foreach ($html_p->getElementsByTagName('p') as $p) {
					$post_content1 .= $html_p->saveHTML($p);
				}
				// Set the timezone so times are calculated correctly
				if($image_path != "NOIMAGE"){
					date_default_timezone_set('Europe/London');
					$image_url        = $image_path; // Define the image URL here
					$image_name       = '1.jpeg';
					$upload_dir       = wp_upload_dir(); // Set upload folder
					$image_data       = file_get_contents($image_url); // Get image data
					$unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); // Generate unique name
					$filename         = basename( $unique_file_name ); // Create image file name
				}
				$user_id = username_exists($post_author);
				if($user_id == ""){
					$user_id = 1;
				}
				
				
				//Create post
				$id = wp_insert_post(array(
					'post_title'    => $post_title,
					'post_content'  => ''.$post_content1.'',
					'post_date'     => $timeStamp,
					'post_author'   => $user_id,
					'post_type'     => 'post',
					'post_status'   => 'future',
				));	
				if($id){
					// Set category - create if it doesn't exist yet
					wp_set_post_terms($id, wp_create_category($post_category), 'category');

					set_post_thumbnail( $id, $thumbnail_id );
					// Add meta data, if required
					add_post_meta($id, 'meta_key', $metadata);
					
					if($image_path != "NOIMAGE")
					{
						// Check folder permission and define file location UPLOAD Feature IMAGE START
						if( wp_mkdir_p( $upload_dir['path'] ) ) {
							$file = $upload_dir['path'] . '/' . $filename;
						} else {
							$file = $upload_dir['basedir'] . '/' . $filename;
						}
						// Create the image  file on the server
						file_put_contents( $file, $image_data );
						// Check image file type
						$wp_filetype = wp_check_filetype( $filename, null );
						// Set attachment data
						$attachment = array(
							'post_mime_type' => $wp_filetype['type'],
							'post_title'     => sanitize_file_name( $filename ),
							'post_content'   => '',
							'post_status'    => 'inherit'
						);
						// Create the attachment
						$attach_id = wp_insert_attachment( $attachment, $file, $post_id );					
						// Define attachment metadata
						$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
						// Assign metadata to attachment
						wp_update_attachment_metadata( $attach_id, $attach_data );
						// And finally assign featured image to post
						set_post_thumbnail( $id, $attach_id );	
						unlink($image_path);
					}					
					wp_set_post_tags( $id, $post_tags, true );
					// Check folder permission and define file location UPLOAD Feature IMAGE END	
				}
				else{
					//echo "WARNING: Failed to insert post into WordPress\n";
				}	
				
			}
		
		}
		sleep(1);
		
	}

}