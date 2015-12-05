<?php
class Sunpop_StampCustomer_IndexController extends Mage_Core_Controller_Front_Action{
    /* public function IndexAction() { 
      
	  $this->loadLayout();   
	  $this->getLayout()->getBlock("head")->setTitle($this->__("Titlename"));
	        $breadcrumbs = $this->getLayout()->getBlock("breadcrumbs");
      $breadcrumbs->addCrumb("home", array(
                "label" => $this->__("Home Page"),
                "title" => $this->__("Home Page"),
                "link"  => Mage::getBaseUrl()
		   ));

      $breadcrumbs->addCrumb("titlename", array(
                "label" => $this->__("Titlename"),
                "title" => $this->__("Titlename")
		   ));

      $this->renderLayout(); 
	  
    }*/
	
	protected function _validateFormKey()  
	{  
		if (!($formKey = $this->getRequest()->getParam('form_key', null)) || $formKey != Mage::getSingleton('core/session')->getFormKey()) {  
			return false;  
		}  
		return true;  
	}
	
	public function ajaxAction(){
		if(!$this->_validateFormKey()){
			 return $this->_redirect('*/*/');  
		}
		$data = $this->getRequest()->getPost();
		$verification = Mage::helper("stampcustomer")->isVerification($data);
		
		switch($verification){
			case 1:
				$result['status'] = false;
				$result['message'] = urlencode($this->__('查询条件不能为空!'));
				$response = Mage::helper('core')->jsonEncode($result);
				$this->getResponse()->setBody(urldecode($response));
				return;
			case 2:
				$result['status'] = false;
				$result['message'] = urlencode($this->__('姓名至少2字!'));
				$response = Mage::helper('core')->jsonEncode($result);
				$this->getResponse()->setBody(urldecode($response));
				return;
			case 3:
				$result['status'] = false;
				$result['message'] = urlencode($this->__('公司至少3字!'));
				$response = Mage::helper('core')->jsonEncode($result);
				$this->getResponse()->setBody(urldecode($response));
				return;
			
		}
		
		try{
			$collection = Mage::getModel("stampcustomer/stampcustomer")->getCollection();
			if($data['a_state']){
				$collection->addFieldToFilter('a_state', array('eq' => trim($data['a_state'])));
			}
			if($data['a_certsn']){
				$collection->addFieldToFilter('a_certsn', array('eq' => trim($data['a_certsn'])));
			}
			if($data['a_stampsn']){
				$collection->addFieldToFilter('a_stampsn', array('eq' => trim($data['a_stampsn'])));
			}
			if($data['a_name']){
				//$names = Mage::helper('core/string')->splitWords($data['a_name']);
				$collection->addFieldToFilter('a_name', array('like' => "%".trim($data['a_name'])."%"));
			}
			if($data['a_company']){
				$collection->addFieldToFilter('a_company', array('like' => "%".trim($data['a_company'])."%"));
			}
			if($data['a_certtype']){
				$collection->addFieldToFilter('a_certtype', array('like' => "%".trim($data['a_certtype'])."%"));
			}
			
			if(count($collection)>0){
				$html = '';
				$html .= '
						<h4>注册人员信息</h4>
						<table>
						<tr> 
							<th>序号</th>
							<th>姓名</th>
							<th>注册区域</th>
							<th>专业类型</th>
							<th>公司</th>
							<th>注册证书号</th>
							<th>印章号</th>
							<th>有效期至</th>
						</tr>';
				foreach($collection as $c){
					$html .= '<tr  class="info">';
					$html .= '<td class="a_id">'.$c->getAId().'</td>';
					$html .= '<td class="a_name">'.$c->getAName().'</td>';
					$html .= '<td class="a_state">'.$c->getAState().'</td>';
					$html .= '<td class="a_certtype">'.$c->getACerttype().'</td>';
					$html .= '<td class="a_company">'.$c->getACompany().'</td>';
					$html .= '<td class="a_certsn">'.$c->getACertsn().'</td>';
					$html .= '<td class="a_stampsn">'.$c->getAStampsn().'</td>';
					$html .= '<td class="a_expdate">'.$c->getAExpdate().'</td>';
					$html .= '</tr>';
				}
				$html .= '</table>';
				$html .='<script type="text/javascript"> 
				jQuery(function($){
					$(".info").click(function(){
						var state = $(this).find(".a_state").html();
						var name = $(this).find(".a_name").html();
						var certtype = $(this).find(".a_certtype").html();
						var company = $(this).find(".a_company").html();
						var certsn = $(this).find(".a_certsn").html();
						var stampsn = $(this).find(".a_stampsn").html();
						var expdate = $(this).find(".a_expdate").html();
						$(".a_state").val(state);
						$(".a_name").val(name);
						$(".a_certtype").val(certtype);
						$(".a_company").val(company);
						$(".a_certsn").val(certsn);
						$(".a_stampsn").val(stampsn);
						if(expdate){
							var date = new Date(expdate);
							var year =date.getFullYear();
							var month =date.getMonth()+1;
							var day =date.getDate();
							$(".year").val(year);
							$(".month").val(month);
							$(".day").val(day);
						}
						/* jQuery(".results").empty(); */
						jQuery(".stampcustomer").hide();
					})
				});
				</script>';
				$result['status'] = true;
				$result['html'] = $html;
				$response = Mage::helper('core')->jsonEncode($result);
				$this->getResponse()->setBody($response);
				return;
			}else{
				$result['status'] = false;
				$result['message'] = urlencode($this->__('结果为空!'));
				$response = Mage::helper('core')->jsonEncode($result);
				$this->getResponse()->setBody(urldecode($response));
				return;
			}
			
		}catch(Exception $e){
			$result['status'] = false;
    		$result['message'] = urlencode($this->__('请重试!'));
			$response = Mage::helper('core')->jsonEncode($result);
			$this->getResponse()->setBody(urldecode($response));
    		return;
		}
		return ;
			
	}
}