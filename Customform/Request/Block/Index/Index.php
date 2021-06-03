<?php
/**
* Copyright © 2016 Nodex . All rights reserved.
*/
namespace Customform\Request\Block\Index;
use Customform\Request\Block\BaseBlock;
class Index extends BaseBlock{
/**
* Returns action url for contact form. Form submit URL
*
* @return string
*/
public function getFormAction(){
return $this->getUrl('request/index/post', ['_secure' => true]);
}
}