<?php 
/**
* The model for generating table view for any model
*/
class TableViewModel extends CI_Model
{
	private $modelFolderName= 'entities';
	private $statusArray = array(1=>'enabled',0=>'disabled');
	private $booleanArray = array(0 =>'No',1=>'Yes' );
	private $defaultPagingLength =100;
	public $export=false;
	function __construct()
	{
		parent::__construct();
		$this->load->model('crud');
		$this->load->model('tableActionModel');
		$this->load->helper('string');
	}
	//create  a method that load the table based on the specified paramterr
	/**
	This  method get the table view of a model(tablename)
	*the $model parameter is the name of the model to generate table view for
	*$page variable paging ability is included as a part of the generated html
	*/
	//model is the name of the class while data is the objects needed to be changed into a table.
	public function getFilteredTableHtml($model,$conditionArray,&$message='',$exclusionArray=array(),$action=null,$paged= true,$start=0,$length=NULL,$resolve=true,$sort=' order by ID desc '){
		if ($paged) {
			$length = $length?$length:$this->defaultPagingLength;
		}
		//use get function for the len and the start index for the sorting
		$start = (isset($_GET['p_start'])&& is_numeric($_GET['p_start']) )?$_GET['p_start']:$start;
		$length = (isset($_GET['p_len'])&& is_numeric($_GET['p_len']) )?$_GET['p_len']:$length;
		$this->load->model($this->modelFolderName.'/'.$model);
		$data = $this->export?$this->$model->getWhereNonObject($conditionArray,$message,0,null,$resolve,$sort):$this->$model->getWhere($conditionArray,$message,$start,$length,$resolve,$sort);
		return $this->export?$this->loadExportTable($model,$data):$this->loadTable($model,$data,$message,$exclusionArray,$action,$paged,$start,$length,$resolve);
	}
	public function loadExportTable($model,$data)
	{
		$res =array2csv($data);
	  	sendDownload($res,'text/csv',$model.'.csv');exit;
	}
	/**
	* $model is the name of the class to load , data is the list of object you want to display as table.
	*/
	public function convertToTable($model,$data,&$message='',$exclusionArray=array(),$action=null,$paged= true,$start=0,$length=NULL){
		loadClass($this->load,$model);
		return $this->loadTable($model,$data,$message='',$exclusionArray,$action,$paged,$start,$length);
	}

	private function loadTable($model,$data,$totalRow,$exclusionArray,$action,$paged,$start,$length,$removeId=true,$appendForm=array()){
		if (!$this->validateModelNameAndAccess($model)) {
			return false;
		}
		$actionArray = $action===null?$model::$tableAction:$action;
		$documentField =$model::$documentField;
		// $totalRow=0;
		if (empty($data) || $data==false) {
			$link = base_url("vc/$model/create");
			return "<div class='empty-data alert alert-primary text-light'>
				NO RECORD FOUND
			</div>";
		}
		$header = $this->getHeader($model,$exclusionArray,$removeId);
		$result=$this->openTable();
		$result.=$this->generateheader($header,$action,$appendForm);
		$result.=$this->generateTableBody($model,$data,$exclusionArray,$actionArray,$appendForm);
		$result.=$this->closeTable();
		// $size = $length?$length:$this->defaultPagingLength;
		if ($paged && $totalRow > $length) {
			$result.=$this->generatePagedFooter($totalRow,$start,$length);//for testing sake
		}
		return $result;
	}
	public function getTableHtml($model,&$message='',$exclusionArray=array(),$action=null,$paged= true,$start=0,$length=NULL,$resolve=true,$sort=' order by ID desc ',$where='',$appendForm=array()){
		loadClass($this->load,$model);
		if ($paged) {
			$length = $length?$length:$this->defaultPagingLength;
		}
		//use get function for the len and the start index for the sorting
		$start = (isset($_GET['p_start'])&& is_numeric($_GET['p_start']) )?(int)$_GET['p_start']:$start;
		$length = (isset($_GET['p_len'])&& is_numeric($_GET['p_len']) )?(int)$_GET['p_len']:$length;
		// $data = $this->export?$this->$model->allNonObject($message,$resolve,0,null,$sort):$this->$model->all($message,$resolve,$start,$length,$sort,$where);
		$data = $this->$model->all($message,$resolve,$start,$length,$sort,$where);
		return $this->export?$this->loadExportTable($model,$data):$this->loadTable($model,$data,$message,$exclusionArray,$action,$paged,$start,$length,true,$appendForm);
		
	}
	public function getTableHtmlExtra($model,&$message='',$exclusionArray=array(),$action=null,$paged= true,$start=0,$length=NULL,$resolve=true,$sort=' order by ID desc '){
		loadClass($this->load,$model);
		if ($paged) {
			$length = $length?$length:$this->defaultPagingLength;
		}
		//use get function for the len and the start index for the sorting
		$start = (isset($_GET['p_start'])&& is_numeric($_GET['p_start']) )?(int)$_GET['p_start']:$start;
		$length = (isset($_GET['p_len'])&& is_numeric($_GET['p_len']) )?(int)$_GET['p_len']:$length;
		$data = $this->export?$this->$model->allNonObject($message,$resolve,0,null,$sort):$this->$model->all($message,$resolve,$start,$length,$sort);
		return $this->export?$this->loadExportTable($model,$data):$this->loadTable($model,$data,$message,$exclusionArray,$action,$paged,$start,$length);
		
	}
	//the action array will contain the 
	private function generateActionHtml($data,$actionArray){//the id will be extracted from
		$result="<ul class='dropdown-menu table-action' data-model=''>";
		$result.=$this->buildHtmlAction($data,$actionArray);
		$result.="</ul>";
		return $result;
	}

	private function buildHtmlAction($data,$actionArray){//the link must be specified for the action that is to be performed
		$result='';
		foreach ($actionArray as $key => $value) {
			$currentid = $data->ID;
			$link = '';
			$classname = get_class($data);
			$critical='0';
			$label ='';
			$default=1;
			if(method_exists($this->tableActionModel,$value)){
				$tempObj = $this->tableActionModel->$value($data);
				if (empty($tempObj)) {
					continue;
				}
				$link = $tempObj['link'].'/'.$currentid;
				$critical = $tempObj['isCritical'];
				$label = $tempObj['label'];
				$default = $tempObj['ajax'];
			}
			else{
				if (is_array($value)) {
					$link = $value[0].'/'.$currentid;
					$critical = $value[1];
					$default = $value[2];
				}
				else{
					$criticalArray = array('delete','disable','reset password','remove');
					if (in_array(strtolower($key), $criticalArray)) {
						$critical =1;
					}
					$link = $value.'/'.$currentid;
				}
				$label = $key;
				$link =base_url($link);
			}
			$editClass='';
			if($label=='edit' || $label=='update'){
				$editClass = "data-ajax-edit='1'";
			}else if ($label == 'editor'){
				$editClass = "data-ajax-editor='1'";
			}else{
				$editClass ='';
			}
			// $editClass = ($label=='edit' ||$label=='update')?"data-ajax-edit='1'":'';
			$result.="<li data-item-id='$currentid' data-default='$default' data-critical='$critical' $editClass><a href='$link' class='dropdown-item text-center text-capitalize'>$label</a></li>";
		}
		return $result;
	}
	private function openTable(){
		return "  <div class=\"box\"><div class=\"table-responsive no-padding\"><table class='table table-bordered'> \n";
	}
	private function generateTableBody($model,$data,$exclusionArray,$actionArray,$appendForm=array()){
		$result ='<tbody>';
		for ($i=0; $i < count($data); $i++) { 
			$current= $data[$i];
			$result.=$this->generateTableRow($model,$current,$exclusionArray,$actionArray,@$_GET['p_start']+$i,$appendForm);
		}
		$result.='</tbody>';
		return $result;
	}
	private function generateTableRow($model,$rowData,$exclusionArray,$actionArray,$index=false,$appendForm=array()){
		$result="<tr data-row-identifier='{$rowData->ID}' class='best-content'>";
		if(!empty($appendForm)){
			extract($appendForm);
			$id = $rowData->ID;
			$inputForm = "<label class='form-check-label'><input type='$type' class='form-check-input $class' name='".$name."[]' id='".$name."[]' value='$id' /></label>";
			$result.="<td><div class='form-check form-check-flat'>$inputForm</div></td>";
		}
		if ($index!==false) {
			$index+=1;
			$result.="<td>$index</td>";
		}
		$documentArray =array_keys($model::$documentField);
		$labels = array_keys($model::$labelArray);
		for ($i=0; $i <count($labels) ; $i++) { 
			$key =$labels[$i];
			if ($key=='ID' || in_array($key, $exclusionArray)) {
				continue;
			}

			$value = $rowData->$key;
		
			if (!empty($documentArray) && in_array($key, $documentArray)) {
				$link = base_url($value);
				$value = "<a href='$link' target='_blank'>Download</a>";
			}
			if ($model::$typeArray[$key]=='tinyint') {
				$value = $value?1:0;
				if ($key == 'status') {
					$value = (!$value)?'disabled':$this->statusArray[$value];
				}
				else{
					$value = $this->booleanArray[$value];
				}
			}

			if(strpos($value,'href')){
				$value = $value;
			}else{
				$value = $value = wordwrap($value,50,"<br />\n",true);
			}
			
			$result.="<td>$value</td>";
		}
		if (!empty($actionArray) && $actionArray!==false) {
			$actionString=$this->generateActionHtml($rowData,$actionArray);
			$result.="<td class='action-column'><div class='dropdown'><span class='dropdown-toggle btn btn-primary' data-toggle='dropdown' aria-haspopup='true' aria-expanded='true'>Action <i class='fa fa-arrow-down'></i></span> 
				$actionString
			</div>
			</td>";
		}
		$result.="</tr>";
		return $result;
	}
	private function closeTable(){
		return '</table></div></div>';
	}
	private function generateHeader($header,$action,$appendForm=array()){
		$sn = "<th>S/N</th>";
		$emptyHeader='';
		if(!empty($appendForm)){
			$emptyHeader = "<th></th>";
		}
		$result="<thead>
			<tr> $emptyHeader $sn";
		for ($i=0; $i < count($header); $i++) {
			$item = $header[$i]; 
			$result.="<th>$item</th>";
		}
		$actionText = '';
		if ($action!==false) {
			$actionText = "<th>Action</th>";
		}
		$result.="	$actionText
				</tr>
			</thead>";
		return $result;
	}
	//this function generate page footer will link to navigate through the pages
	 function generatePagedFooter($totalPage,$currentcount,$pageLength){
	 	$beginCount =10;
	 	if ($totalPage <= $pageLength) {
	 		return;
	 	}
		$result="<div class='paging'>
		<div style='display:inline_block'>page size : <input type='text' style='width:50px;display:inline_block;background-color:white;' id='page_size'  value='$pageLength' /></div>
			<ul class='pagination pagination-sm no-margin'>
			";
		// $pageArray=$this->generatePageArray($totalPage,$pageLength);
		$totalPaged = ceil($totalPage/$pageLength);
		$currentIndex =$this->calculateCurrentIndex($currentcount,$pageLength);
		$start=0;
		if ($totalPaged > ($beginCount)) {
			$start=$currentIndex-(ceil($beginCount/2)+1) ;//half of the content before showing the current index
			$start =$start<0?0:$start;
			// $len = $start+$beginCount;
			$prev = $currentIndex> 0? ($currentIndex-1):0;
			$prev*=$pageLength;
			$disable = $prev==0?'disabled':'';
			$result.="<li data-start='$prev' data-length='$pageLength' class='paged-item $disable'>«</li>";
			$len = $start+ceil($beginCount/2);
			for ($i=$start; $i < $len; $i++) { 
				$current =1+ $i;
				$itemClass ='paged-item';
				if ($i==$currentIndex) {
					$itemClass ='active paged-item';
				}
				$end = $current*$pageLength;
				$result.="<li data-start='$end' data-length='$pageLength' class='$itemClass '>$current</li>";
				// $start = $end;
			}
			$result.="<li data-start='' data-length='$pageLength' class='paged-item  break'>...</li>";
			$len =floor($beginCount/2);
			$start = ($totalPaged-(1+$len));
			$len+=$start;
			for ($i=$start; $i < $len; $i++) { 
				$current =1+ $i;
				$itemClass ='paged-item';
				if ($i==$currentIndex) {
					$itemClass ='paged-item active';
				}
				$end = $current * $pageLength;
				$result.="<li data-start='$end' data-length='$pageLength' class='$itemClass '>$current</li>";
				// $start = $end;
			}
			$prev = $currentIndex < $totalPaged? ($currentIndex+1):$totalPaged-1;
			$last =$prev *$pageLength;
			$result.="<li data-start='$last' data-length='$pageLength' class='paged-item'>»</li>";
			$len = $start+$beginCount;
		}
		else{
			for ($i=0; $i < $totalPaged; $i++) { 
				$current =1+ $i;
				$itemClass ='paged-item';
				if ($i==$currentIndex) {
					$itemClass ='paged-item active';
				}
				$end = $start * $pageLength;
				$result.="<li data-start='$end' data-length='$pageLength' class='$itemClass'>$current</li>";
				// $start = $end;
			}
		}
		$result.="<div class='clear'></div></ul>
		</div>";
		return $result."<div class='clear'></div>";
	}
	private function calculateCurrentIndex($current,$pageLength){
		return ceil($current/$pageLength);
	}
	private function generatePageArray($totalPage,$pageLength){
		$count = ceil(($totalPage/$pageLength));
		$result= array();
		for ($i=0; $i < $count ; $i++) { 
			$result[]=$i+1;
		}
		return $result;
	}
	//create another method to generate the needed javascript file for the paging this can be called independently of the getTableHtml function
	//this functin basically generates the javascript file needed to process  the action as well as the paging function search functionality will also be included automatically
	public function getJSData($actionArray){

	}
	private function getHeader($model,$exclusionArray,$removeid){
		$result = array();
		$labels = $model::$labelArray;
		foreach ($labels as $key => $value) {
			if ($key=='ID' || in_array($key, $exclusionArray)) {//dont include the header if its an id field or
				continue;
			}
			if (empty($value)) {
				if ($removeid && endsWith($key,'_id')) {
					$key = substr($key, 0,strlen($key)-strlen('_id'));
				}
				$result[] = removeUnderscore($key);
			}
			else{
				$result[]=$value;
			}
		}
		return $result;
	}
	//this function validate the correctnes of the moe
	private function validateModelNameAndAccess($modelname){
		$message='';
		if (empty($modelname)) {
			throw new Exception('empty model name not allowed'); 
			return false;
		}
		if (!is_subclass_of($this->$modelname, 'Crud')) {
			throw new Exception('model is not a crud: make sure the correct name of the model is entered');
			return false;
		}
		if (!$this->validateAccess($modelname)) {
			throw new Exception("access denied");
			return false;
		}
		return true;
	}

	private function validateAccess($modelname){
		return true;//for now. change the implementation later//the implementation will make a call to the accessControl model
	}
}
 ?>