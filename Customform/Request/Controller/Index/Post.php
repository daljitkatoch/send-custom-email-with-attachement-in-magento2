<?php
/**
*
* Copyright © 2016 Node web commerce. All rights reserved.
*/
namespace Customform\Request\Controller\Index;
use Magento\Framework\Controller\ResultFactory;
/*use Magento\Framework\Mail\Template\TransportBuilder;*/
use Customform\Request\Model\Mail\Template\TransportBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Controller\Result\Redirect;

use Magento\Framework\App\ObjectManager;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;


class Post extends \Magento\Framework\App\Action\Action{
    
	const FOLDER_LOCATION = 'contact_request_attachment';

	protected $transportBuilder;
    protected $storeManager;
    protected $inlineTranslation;	
		
	protected $_cacheTypeList;
	protected $_cacheState;
	protected $_cacheFrontendPool;
	protected $resultPageFactory;
	private $logger;
	
	
	private $fileUploaderFactory;
    private $fileSystem;

	public function __construct(
	\Magento\Framework\App\Action\Context $context,
	\Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
	\Magento\Framework\App\Cache\StateInterface $cacheState,
	\Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
	\Magento\Framework\View\Result\PageFactory $resultPageFactory,
	TransportBuilder $transportBuilder,	
	\Magento\Store\Model\StoreManagerInterface $storeManager,
	\Magento\Framework\Translate\Inline\StateInterface $state,
	\Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
    \Magento\Framework\Filesystem $fileSystem,
	\Magento\Framework\Filesystem\Io\File $file,
	\Psr\Log\LoggerInterface $logger = null
	) {
	parent::__construct($context);
		$this->_cacheTypeList = $cacheTypeList;
		$this->_cacheState = $cacheState;
		$this->_cacheFrontendPool = $cacheFrontendPool;
		$this->resultPageFactory = $resultPageFactory;
		$this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->inlineTranslation = $state;
		$this->logger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
		
		$this->fileUploaderFactory = $fileUploaderFactory;
        $this->fileSystem = $fileSystem;
		$this->file = $file;
	}
	/**
	* Flush cache storage
	*
	*/
	public function execute(){
		
		// this is an example and you can change template id,fromEmail,toEmail,etc as per your need.
        $templateId = 'customemail_email_template'; // template id
        $fromEmail = 'owner@owner.com';  // sender Email id
        $fromName = 'Website Name';             // sender Name
        $toEmail = 'noreply@owner.com'; // receiver email id

        try {
            // template variables pass here
            $post = $this->getRequest()->getPost();
            $templateVars = [
                'fname' => $post['firstname'],
                'lname' => $post['lastname'],
                'email' => $post['email'],               
                'phone' => $post['phone'],
                'street_address' => $post['street_address'],
                'apartment' => $post['apartment'],
                'country' => $post['country'],
                'region' => $post['region'],
                'city' => $post['city'],
                'postcode' => $post['postcode'],                
                'attachment' => $post['attachment'],
            ];

            $storeId = $this->storeManager->getStore()->getId();

            $from = ['email' => $fromEmail, 'name' => $fromName];
            $this->inlineTranslation->suspend();

            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $templateOptions = [
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $storeId
            ];
			
			/**send attachment code**/
			$filePath = null;
			$fileName = null;
			$uploaded = false;
			try {
				$fileCheck = $this->fileUploaderFactory->create(['fileId' => 'attachment']);
				$file = $fileCheck->validateFile();
				$attachment = $file['name'] ?? null;
			} catch (\Exception $e) {
				$attachment = null;
			}
			if ($attachment) {
				$upload = $this->fileUploaderFactory->create(['fileId' => 'attachment']);
				$upload->setAllowRenameFiles(true);
				$upload->setFilesDispersion(true);
				$upload->setAllowCreateFolders(true);
				$upload->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png', 'pdf']);

				$path = $this->fileSystem
					->getDirectoryRead(DirectoryList::MEDIA)
					->getAbsolutePath(self::FOLDER_LOCATION);
				$result = $upload->save($path);
				$uploaded = self::FOLDER_LOCATION . $upload->getUploadedFilename();
				$filePath = $result['path'] . $result['file'];
				$fileName = $result['name'];
			}
			/**end attachment code**/
			

            $transport = $this->transportBuilder->setTemplateIdentifier($templateId, $storeScope)
                ->setTemplateOptions($templateOptions)
                ->setTemplateVars($templateVars)
                ->setFrom($from)
                ->addTo($toEmail)
                ->getTransport();
				
				
			/**send attachment code**/
			if ($uploaded && !empty($filePath) && $this->file->fileExists($filePath)) {
				$mimeType = mime_content_type($filePath);

				$transport = $this->transportBuilder
					->setTemplateIdentifier($templateId, $storeScope)
					->setTemplateOptions($templateOptions)
					->addAttachment($this->file->read($filePath), $fileName, $mimeType)
					->setTemplateVars($templateVars)
					->setFrom($from)
					->addTo($toEmail)
					//->setReplyTo($replyTo, $replyToName)
					->getTransport();
			}
			/**end attachment code**/
			
            $transport->sendMessage();
            $this->inlineTranslation->resume();
			$this->messageManager->addSuccessMessage('Request sent successfully.');
            //$this->_redirect('request/index/index');
			return $this->resultRedirectFactory->create()->setPath('request/index');
			
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
			echo $e->getMessage();
        }
		
	}
}

