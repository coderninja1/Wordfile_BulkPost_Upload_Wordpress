<?php
class DocxImages {
	private $file;
	private $indexes = [ ];
	/** Local directory name where images will be saved */
	private $savepath = 'docimages';
	private $fileData = false;
    private $errors = array();
    private $styles = array();
	public function __construct( $filePath ) {
		$this->file = $filePath;
		$this->extractImages();
	}
	function extractImages() {
		$ZipArchive = new ZipArchive;
		if ( true === $ZipArchive->open( $this->file ) ) {
			for ( $i = 1; $i < $ZipArchive->numFiles; $i ++ ) {
				$zip_element = $ZipArchive->statIndex( $i );
				if ( preg_match( "([^\s]+(\.(?i)(jpg|jpeg|png|gif|bmp))$)", $zip_element['name'] ) ) {				
					$imagename                   = explode( '/', $zip_element['name'] );
					$imagename                   = end( $imagename );
					$imagename = round(microtime(true)) . '.' . $imagename;
					$this->indexes[ $imagename ] = $i;
				}
			}
		}
	}
	function saveAllImages() {
		if ( count( $this->indexes ) == 0 ) {
		}
		foreach ( $this->indexes as $key => $index ) {
			$zip = new ZipArchive;
			if ( true === $zip->open( $this->file ) ) {
				file_put_contents( dirname( __FILE__ ) . '/' . $this->savepath . '/' . $key, $zip->getFromIndex( $index ) );
			}
			$zip->close();
		}
	}
	function displayImages() {
		$this->saveAllImages();
		if ( count( $this->indexes ) == 0 ) {
			return 'NOIMAGE';
		}
		$images = '';
		foreach ( $this->indexes as $key => $index ) {
			$path = $this->savepath . '/' . $key;
			$images .= '<img src="' . $path . '" alt="' . $key . '"/> <br>';
		}
	return $path;
	}
	
	private function load($file) {
        if (file_exists($file)) {
            $zip = new ZipArchive();
            $openedZip = $zip->open($file);
            if ($openedZip === true) {
                if (($styleIndex = $zip->locateName('word/styles.xml')) !== false) {
                    $stylesXml = $zip->getFromIndex($styleIndex);
                    $xml = simplexml_load_string($stylesXml);
                    $namespaces = $xml->getNamespaces(true);
                    $children = $xml->children($namespaces['w']);
                    foreach ($children->style as $s) {
                        $attr = $s->attributes('w', true);
                        if (isset($attr['styleId'])) {
                            $tags = array();
                            $attrs = array();
                            foreach (get_object_vars($s->rPr) as $tag => $style) {
                                $att = $style->attributes('w', true);
                                switch ($tag) {
                                    case "b":
                                        $tags[] = 'h1';
                                        break;
                                    case "i":
                                        $tags[] = 'em';
                                        break;
                                    case "color":
                                        $attrs[] = 'color:#' . $att['val'];
                                        break;
                                    case "sz":
                                        $attrs[] = 'font-size:' . $att['val'] . 'px';
                                        break;
                                }
                            }
                            $styles[(String)$attr['styleId']] = array('tags' => $tags, 'attrs' => $attrs);
                        }
                    }
                    $this->styles = $styles;
                }

                if (($index = $zip->locateName('word/document.xml')) !== false) {
                    // If found, read it to the string
                    $data = $zip->getFromIndex($index);

                    $zip->close();
                    return $data;
                }
                $zip->close();
            } else {
                switch($openedZip) {
                    case ZipArchive::ER_EXISTS:
                        $this->errors[] = 'File exists.';
                        break;
                    case ZipArchive::ER_INCONS:
                        $this->errors[] = 'Inconsistent zip file.';
                        break;
                    case ZipArchive::ER_MEMORY:
                        $this->errors[] = 'Malloc failure.';
                        break;
                    case ZipArchive::ER_NOENT:
                        $this->errors[] = 'No such file.';
                        break;
                    case ZipArchive::ER_NOZIP:
                        $this->errors[] = 'File is not a zip archive.';
                        break;
                    case ZipArchive::ER_OPEN:
                        $this->errors[] = 'Could not open file.';
                        break;
                    case ZipArchive::ER_READ:
                        $this->errors[] = 'Read error.';
                        break;
                    case ZipArchive::ER_SEEK:
                        $this->errors[] = 'Seek error.';
                        break;
                }
            }
        }else {
            $this->errors[] = 'File does not exist.';
        }
    }

    public function setFile($path) {
        $this->fileData = $this->load($path);
    }

    public function to_plain_text() {
        if ($this->fileData) {
            return strip_tags($this->fileData);
        } else {
            return false;
        }
    }
	
    public function to_html() {
        if ($this->fileData) {
            $xml = simplexml_load_string($this->fileData);
            $namespaces = $xml->getNamespaces(true);

            $children = $xml->children($namespaces['w']);

            $html = '<!doctype html><html><head><meta http-equiv="Content-Type" content="text/html;charset=utf-8" /><title></title><style>span.block { display: block; }</style></head><body>';

            foreach ($children->body->p as $p) {
                $style = '';
                
                $startTags = array();
                $startAttrs = array();
                
                if($p->pPr->pStyle) {                    
                    $objectAttrs = $p->pPr->pStyle->attributes('w',true);
                    $objectStyle = (String) $objectAttrs['val'];
                    if(isset($this->styles[$objectStyle])) {
                        $startTags = $this->styles[$objectStyle]['tags'];
                        $startAttrs = $this->styles[$objectStyle]['attrs'];
                    }
                }
                
                if ($p->pPr->spacing) {
                    $att = $p->pPr->spacing->attributes('w', true);
                    if (isset($att['before'])) {
                        $style.='padding-top:' . ($att['before'] / 10) . 'px;';
                    }
                    if (isset($att['after'])) {
                        $style.='padding-bottom:' . ($att['after'] / 10) . 'px;';
                    }
                }

                $html.='<span class="block" style="' . $style . '">';
                $li = false;
                if ($p->pPr->numPr) {
                    $li = true;
                    $html.='<li>';
                }
                
                foreach ($p->r as $part) {
                   
						$tags = $startTags;
						$attrs = $startAttrs;      
						
						foreach (get_object_vars($part->pPr) as $k => $v) {
							if ($k = 'numPr') {
								$tags[] = 'li';
							}
						}

						foreach (get_object_vars($part->rPr) as $tag => $style) {
							$att = $style->attributes('w', true);
							switch ($tag) {
								case "i":
									$tags[] = 'em';
									break;
								case "color":
									//echo (String) $att['val'];
									$attrs[] = 'color:#' . $att['val'];
									break;
								case "sz":
									$attrs[] = 'font-size:' . $att['val'] . 'px';
									break;
								case "szCs":
									$attrs[] = 'font-size:' . $att['val'] . 'px';
									break;
							}	
						}
						foreach ($attrs as $attr) {
						   if($attr == 'font-size:48px')
						   {
								$tags[] = 'h1';
						   }
							if($attr == 'font-size:44px')
						   {
								$tags[] = 'h2';
						   }
							if($attr == 'font-size:36px')
						   {
								$tags[] = 'h3';
						   }
							if($attr == 'font-size:32px')
						   {
								$tags[] = 'h4';
						   }
							if($attr == 'font-size:24px')
						   {
								$tags[] = 'h5';
						   }
							if($attr == 'font-size:20px')
						   {
								$tags[] = 'h6';
						   }
						}
						$openTags = '';
						$closeTags = '';
						foreach ($tags as $tag) {
							$openTags='<' . $tag . '>';
							$closeTags='</' . $tag . '>';
						}
						
						
						
						 $html.='<span style="' . implode(';', $attrs) . '">' . $openTags . $part->t . $closeTags . '</span>';
						echo "</br>";
					
					
				}
                if ($li) {
                    $html.='</li>';
                }
                $html.="</span>";
            }
            $regex = <<<'END'
/
  (
    (?: [\x00-\x7F]                 # single-byte sequences   0xxxxxxx
    |   [\xC0-\xDF][\x80-\xBF]      # double-byte sequences   110xxxxx 10xxxxxx
    |   [\xE0-\xEF][\x80-\xBF]{2}   # triple-byte sequences   1110xxxx 10xxxxxx * 2
    |   [\xF0-\xF7][\x80-\xBF]{3}   # quadruple-byte sequence 11110xxx 10xxxxxx * 3 
    ){1,100}                        # ...one or more times
  )
| .                                 # anything else
/x
END;
            preg_replace($regex, '$1', $html);

            return $html . '</body></html>';
            exit();
        }
    }
	
	
	    public function to_html1() {
        if ($this->fileData) {
            $xml = simplexml_load_string($this->fileData);
            $namespaces = $xml->getNamespaces(true);

            $children = $xml->children($namespaces['w']);

            $html = '<!doctype html><html><head><meta http-equiv="Content-Type" content="text/html;charset=utf-8" /><title></title></head><body>';

            foreach ($children->body->p as $p) {
                $style = '';
                
                $startTags = array();
                $startAttrs = array();
                
                if($p->pPr->pStyle) {                    
                    $objectAttrs = $p->pPr->pStyle->attributes('w',true);
                    $objectStyle = (String) $objectAttrs['val'];
                    if(isset($this->styles[$objectStyle])) {
                        $startTags = $this->styles[$objectStyle]['tags'];
                        $startAttrs = $this->styles[$objectStyle]['attrs'];
                    }
                }
                
                if ($p->pPr->spacing) {
                    $att = $p->pPr->spacing->attributes('w', true);
                    if (isset($att['before'])) {
                        $style.='padding-top:' . ($att['before'] / 10) . 'px;';
                    }
                    if (isset($att['after'])) {
                        $style.='padding-bottom:' . ($att['after'] / 10) . 'px;';
                    }
                }

                $html.='<p style="' . $style . '">';
                $li = false;
                if ($p->pPr->numPr) {
                    $li = true;
                    $html.='<li>';
                }
                
                foreach ($p->r as $part) {
                    //echo $part->t;
                    $tags = $startTags;
                    $attrs = $startAttrs;                                        

                    foreach (get_object_vars($part->pPr) as $k => $v) {
                        if ($k = 'numPr') {
                            $tags[] = 'li';
                        }
                    }

                    foreach (get_object_vars($part->rPr) as $tag => $style) {
                        //print_r($style->attributes());
                        $att = $style->attributes('w', true);
                        switch ($tag) {
                            case "b":
                                $tags[] = 'strong';
                                break;
                            case "i":
                                $tags[] = 'em';
                                break;
                            case "color":
                                //echo (String) $att['val'];
                                $attrs[] = 'color:#' . $att['val'];
                                break;
                            case "sz":
                                $attrs[] = 'font-size:' . $att['val'] . 'px';
                                break;
                        }
                    }
					foreach ($attrs as $attr) {
						   if($attr == 'font-size:24px')
						   {
                    $openTags = '';
                    $closeTags = '';
                    foreach ($tags as $tag) {
                        $openTags.='<' . $tag . '>';
                        $closeTags.='</' . $tag . '>';
                    }
                    $html.='<pstyle="' . implode(';', $attrs) . '">' . $openTags . $part->t . $closeTags . '</p>';
					   }
					}
	
                }
                if ($li) {
                    $html.='</li>';
                }
            }

            //Trying to weed out non-utf8 stuff from the file:
            $regex = <<<'END'
/
  (
    (?: [\x00-\x7F]                 # single-byte sequences   0xxxxxxx
    |   [\xC0-\xDF][\x80-\xBF]      # double-byte sequences   110xxxxx 10xxxxxx
    |   [\xE0-\xEF][\x80-\xBF]{2}   # triple-byte sequences   1110xxxx 10xxxxxx * 2
    |   [\xF0-\xF7][\x80-\xBF]{3}   # quadruple-byte sequence 11110xxx 10xxxxxx * 3 
    ){1,100}                        # ...one or more times
  )
| .                                 # anything else
/x
END;
            preg_replace($regex, '$1', $html);

            return $html . '</body></html>';
            exit();
        }
    }
	

    public function get_errors() {
        return $this->errors;
    }

    private function getStyles() {
        
    }
	
	function insert($arrData , $strTblName){
		  	global $objConnection;
		  

		  	foreach ($arrData as $strkey => $strvalue) {

		  			$values[]  = $strvalue;
		  	}

		  	$strfields  = '`' . implode( '`, `', array_keys( $arrData ) ) . '`';
		  	$strvalues = implode( "', '", $values );

		  	$sql = "INSERT INTO `$strTblName` ($strfields) VALUES ('$strvalues')";
		 
		 	$stmt = $objConnection->prepare($sql);

		 	$stmt=$stmt->execute();

		  	return $objConnection->lastInsertId('id');

		  	//$objConnection->close();

		  }

		  function fnIsFileExist($file_name,$strTblName){
		  	global $objConnection;
		    $sql = "SELECT * FROM  $strTblName where file_name LIKE '$file_name%'";
		    $stmt = $objConnection->prepare($sql);
		 	$stmt->execute();
		    $result=$stmt->fetch();
		  	return  $result;
		  }
}
/** It will only save images to local server */
#$DocxImages->saveAllImages();
?>