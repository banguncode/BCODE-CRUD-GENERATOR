<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\API\ResponseTrait;
use Config\Exceptions;

class Bcode extends Controller{
   use ResponseTrait;

	public function __construct()
	{
      helper('inflector');
      $this->validation = \Config\Services::validation();
      $this->db = \Config\Database::connect();
      $this->table = new \CodeIgniter\View\Table();
	}

   /**
   * Halaman Generator
   *
   * Baca instruksi yang terdapat di bagian header.
   * Konten sisi kiri memuat form generator.
   * Konten sisi kiri memuat log aktifitas generator
   */
	public function index(){
		try
		{
			$tables = $this->db->listTables();
			$data = [
				'tables' => $tables,
				'content' => 'bcode/content/home'
			];
		}
		catch (\Exception $e)
		{
			$data = [
				'message' => $e->getMessage(),
				'content' => 'bcode/content/error'
			];
		}
      return view('bcode/main', $data);
	}

   /**
   * Proses Generator
   *
   * Proses pembuatan model, view, dan controller.
   * Terdapat validasi umum terhadap field-field yang ada.
   * Terdapat validasi untuk memilih tabel yang dimaksud
   * jika terdapat relasi antara keduanya. Terdapat validasi
   * format penamaan model dan controller
   */
	public function generate(){
      $request = $this->request->getPost();
      $this->rules();
      $data = [];
      if ($this->validation->run($request) == FALSE) {

         $message = $this->validation->getErrors();
         return $this->fail($message);
      } else {
         $controllerName = $request['controller'];
         $modelName = $request['model'];

         if ($this->position($controllerName) == false || $this->position($modelName) == false) {

            if ($this->position($controllerName) == false)
               $message['controller'] = 'The Controller field must contain the word "Bcode"';
            if ($this->position($modelName) == false)
               $message['model'] = 'The Model field must contain the word "Bcode"';

            return $this->fail($message);
         }

         else {
            // pecah data table
            foreach ($request['table'] as $tables => $table) {
               $format = ['Bcode'];
               $controllerName = $this->replace($request['controller'], $table);
               $otherModelName = [];

               $fieldData = $this->db->getFieldData($table);
               $foreignKeyData = $this->db->getForeignKeyData($table);

               $nonPk = [];

               // pecah kolom
               foreach ($fieldData as $col) {
                  $col->primary_key == 1 ? $pk = $col : $nonPk[] = $col;
               }
               // .pecah kolom

               // pecah non pk
               $groupNonPkName = [];
               foreach ($nonPk as $cols => $col) {
                  if ($col->type == 'enum') {
                     $getRow = $this->db->query("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_NAME = '$table' AND COLUMN_NAME = '$col->name'")->getRowArray();

                     $enumList = explode(",", str_replace("'", "", substr($getRow['COLUMN_TYPE'], 5, (strlen($getRow['COLUMN_TYPE'])-6))));


                     foreach ($enumList as $enum)
                     {
                        $col->option_value[] = $enum;
                     }
                  }
                  // grup nama non pk
                  // if ($col->type == 'tinyblob' || $col->type == 'blob' || $col->type == 'mediumblob' || $col->type == 'longblob') {
                     $groupNonPkName[] = $col->name;
                  // }
                  // .grup nama non pk
               }
               // .pecah non pk

               // cek kolom mime type jika terdapat tipe data blob
               foreach ($nonPk as $cols => $col) {
                  if ($col->type == 'tinyblob' || $col->type == 'blob' || $col->type == 'mediumblob' || $col->type == 'longblob') {
                     if (!in_array($col->name.'_media_type', $groupNonPkName)){
                        $message[$col->name] = 'The "'.$col->name.'" column was detected as a BLOB data type. An additional "'.$col->name.'_media_type" column in the "'.$table.'" table is required to store Mime Type data from a file.';
                        return $this->fail($message);
                     }
                  }
               }
               // .cek kolom mime type jika terdapat tipe data blob

               // cek fk
               if (!empty($foreignKeyData)) {
                  // pecah data fk
                  foreach ($foreignKeyData as $fks => $fk) {
                     if (!in_array($fk->foreign_table_name, $request['table'])) {

                        $message[$fk->foreign_table_name] = 'The "'.$fk->foreign_table_name.'" table is required because the "'.$fk->column_name.'" column in the "'.$fk->table_name.'" table is related to the "'.$fk->foreign_column_name.'" column in the "'.$fk->foreign_table_name.'" table.';

                        return $this->fail($message);
                     }

                     else{
                        $otherModelName[] = $this->replace($request['model'], $fk->foreign_table_name);
                        $modelName = $this->replace($request['model'], $table);
                     }
                  }
                  // .pecah data fk
               }
               else{
                  $modelName = $this->replace($request['model'], $table);
               }
               // .cek fk

               $data[] = [
                  'pk' => $pk,
                  'non_pk' => $nonPk,
                  'fk' => $foreignKeyData,
                  'table' => $table,
                  'controller' => $controllerName,
                  'model' => $modelName,
                  'other_model' => $otherModelName
               ];

            }
            // .pecah data table
            $this->createModel($data);
            $this->createView($data);
            $this->createController($data);
            $message = 'Success! generated successfully';

            return $this->respond($message);
         }
      }
	}

   /**
   * Aturan Generator
   *
   * Aturan-aturan yang berlaku saat melakukan generate
   * yatu field table, field model, dan field controller.
   */
   private function rules(){
      $this->validation->setRules([
         'table' =>
         [
            'label'  => 'Table',
            'rules'  => 'required'
         ],
         'model' =>
         [
            'label'  => 'Model',
            'rules'  => 'required|regex_match[/^[A-Za-z_]+$/]'
         ],
         'controller' => [
            'label'  => 'Controller',
            'rules'  => 'required|regex_match[/^[A-Za-z_]+$/]'
         ],
      ]);

   }

   /**
    * Format Penamaan Controller dan Model
    *
    * Pada nama controller dan nama model wajib
    * mencantumkan kata "Bcode". Secara bawaan nama model
    * adalah "Bcode", dan nama model yaitu "BcodeModel".
    * Penambahan prefix yang bersifat opsional.
    * @access private
    * @param string
	 * @return bool
    */
   private function position($string){
      $format = 'Bcode';

      if (strpos($string, $format) !== false) {
         return true;
      }
      else{
         return false;
      }
   }

   /**
   * Timpa Kata Bcode Menjadi Nama Tabel
   *
   * Hal ini bertujuan untuk mengidentifikasi penamaan
   * controller dan model
   * @access private
   * @param string
   * @return string
   */
   private function replace($string, $table){
      $format = 'Bcode';

      return str_replace($format, pascalize($table), $string);
   }

   /**
   * Membuat Model
   *
   * Terdapat file model sesuai banyaknya tabel.
   * Terdapat inisialisasi nama table, primary key,
   * filed-field yang diperbolehkan untuk diisi dan
   * field-field yang diperbolehkan untuk pencarian..
   * Terdapat beberapa method yang digunakan untuk
   * menampilkan data dari permintaan datatable.
   * @access public
   * @param array
   */
    public function createModel($request = [])
    {
      foreach ($request as $datas => $data) {
         $modelTemplate = '<?php
namespace App\Models;
use CodeIgniter\Model;

class '.$data['model'].' extends Model{
   protected $table      = \''.$data['table'].'\';';

            $modelTemplate .= '
   protected $primaryKey = \''.$data['pk']->name.'\';';

         $modelTemplate .= '
   protected $allowedFields = [';
         // pecah non pk
         $af = 1;
         foreach ($data['non_pk'] as $nonPks => $nonPk) {
            $modelTemplate .= '\''.$nonPk->name.'\'';
            if ($af < count($data['non_pk'])) {
               $modelTemplate .= ', ';
            }
            $af++;
         }
         // .pecah non pk
         $modelTemplate .= '];';

         $modelTemplate .= '
   protected $searchFields = [';
            // pecah non pk
            $sf = 0;
            foreach ($data['non_pk'] as $nonPks => $nonPk) {
               if ($sf < 5)
                  $modelTemplate .= '\''.$nonPk->name.'\'';
               if ($sf < 4)
                  $modelTemplate .= ', ';

               $sf++;
            }
            // .pecah non pk
         $modelTemplate .= '];';


         $modelTemplate .= '

   public function filter($search = null, $limit = null, $start = null, $orderField = null, $orderDir = null){
      $builder = $this->table($this->table);

      $i = 0;
      foreach ($this->searchFields as $column)
      {
            if($search)
            {
               if($i == 0)
               {
                  $builder->groupStart()
                          ->like($column, $search);
               }
               else
               {
                  $builder->orLike($column, $search);
               }

               if(count($this->searchFields) - 1 == $i) $builder->groupEnd();

            }
            $i++;
      }

      // Secara bawaan menampilkan data sebanyak kurang dari
      // atau sama dengan 6 kolom pertama.
      $builder->select(\''.$data['pk']->name.', ';

      $qr=0;
      foreach ($data['non_pk'] as $nonPks => $nonPk) {
         if($qr < 5)
            $modelTemplate .= $nonPk->name;
         if($qr < 4)
            $modelTemplate .= ', ';
         $qr++;
      }

      $modelTemplate .= '\')';

      if(!empty($data['fk'])){
         foreach ($data['fk'] as $fks => $fk) {
                  $modelTemplate .= '
              ->join(\''.$fk->foreign_table_name.'\', \''.$fk->foreign_table_name.'.'.$fk->foreign_column_name.' = '.$fk->table_name.'.'.$fk->column_name.'\')';
         }
      }

      $modelTemplate .= '
              ->orderBy($orderField, $orderDir)
              ->limit($limit, $start);

      $query = $builder->get()->getResultArray();

      foreach ($query as $index => $value) {';
         foreach ($data['non_pk'] as $nonPks => $nonPk) {
            if($nonPk->type == 'tinytext' || $nonPk->type == 'text' || $nonPk->type == 'mediumtext' || $nonPk->type == 'longtext'){
            $modelTemplate .= '
         $query[$index][\''.$nonPk->name.'\'] = strlen($query[$index][\''.$nonPk->name.'\']) > 50 ? substr($query[$index][\''.$nonPk->name.'\'], 0, 50).\'...\' : $query[$index][\''.$nonPk->name.'\'];
         ';
            }

            if($nonPk->type == 'tinyblob' || $nonPk->type == 'blob' || $nonPk->type == 'mediumblob' || $nonPk->type == 'longblob'){
            $modelTemplate .= '
         if(!empty($query[$index][\''.$nonPk->name.'\'])){
            $rand'.pascalize($nonPk->name).' = md5(rand());
            $'.camelize($nonPk->name).' = date(\'Ymd\').\'-\'.$this->table.\'-\'.$rand'.pascalize($nonPk->name).';
            $query[$index][\''.$nonPk->name.'\'] = \' <div class="btn-group" role="group" aria-label="Action"><a class="btn btn-sm btn-outline-info" data-toggle="collapse" href="#data-\'.$rand'.pascalize($nonPk->name).'.\'" role="button" aria-expanded="false" aria-controls="collapseExample">Show</a><a class="btn btn-sm btn-outline-success" href="data:\'.$query[$index][\''.$nonPk->name.'_media_type\'].\';base64,\'.$query[$index][\''.$nonPk->name.'\'].\'" download="\'.$'.camelize($nonPk->name).'.\'">Download</a></div><div class="collapse mt-2" id="data-\'.$rand'.pascalize($nonPk->name).'.\'"><div class="card card-body"><object style="width:100%;height:auto" data="data:\'.$query[$index][\''.$nonPk->name.'_media_type\'].\';base64,\'.$query[$index][\''.$nonPk->name.'\'].\'" ></object></div></div>\';
         }
         ';
            }
         }

         $modelTemplate .= '
         $query[$index][\'column_bulk\'] = \'<input type="checkbox" class="bulk-item" value="\'.$query[$index][$this->primaryKey].\'">\';
         $query[$index][\'column_action\'] = \'<div class="btn-group" role="group" aria-label="Action"><button class="btn btn-sm btn-xs btn-info form-action" item-id="\'.$query[$index][$this->primaryKey].\'" purpose="detail">Detail</button> <button class="btn btn-sm btn-xs btn-warning form-action" purpose="edit" item-id="\'.$query[$index][$this->primaryKey].\'">Edit</button></div>\';
      }
      return $query;
   }

   public function countTotal(){
      return $this->table($this->table)';

         if(!empty($data['fk'])){
            foreach ($data['fk'] as $fks => $fk) {
                     $modelTemplate .= '
                  ->join(\''.$fk->foreign_table_name.'\', \''.$fk->foreign_table_name.'.'.$fk->foreign_column_name.' = '.$fk->table_name.'.'.$fk->column_name.'\')';
            }
         }

         $modelTemplate .= '
                  ->countAll();
   }

   public function countFilter($search){
      $builder = $this->table($this->table);

      $i = 0;
      foreach ($this->searchFields as $column)
      {
            if($search)
            {
               if($i == 0)
               {
                  $builder->groupStart()
                          ->like($column, $search);
               }
               else
               {
                  $builder->orLike($column, $search);
               }

               if(count($this->searchFields) - 1 == $i) $builder->groupEnd();

            }
            $i++;
      }

      return $builder';

      if(!empty($data['fk'])){
         foreach ($data['fk'] as $fks => $fk) {
                  $modelTemplate .= '->join(\''.$fk->foreign_table_name.'\', \''.$fk->foreign_table_name.'.'.$fk->foreign_column_name.' = '.$fk->table_name.'.'.$fk->column_name.'\')
                     ';
         }
      }

      $modelTemplate .= '->countAllResults();
   }

}';

         // make model
         $formFile = fopen(APPPATH.'Models/'.$data['model'].'.php','w')
            or $this->failServerError("Access to the path '".APPPATH."' is denied. Change your permision path to 777.");
         fwrite($formFile, $modelTemplate);
         fclose($formFile);
         // .make model
      }
    }

   /**
   * Membuat View
   *
   * Terdapat dua file view yaitu list.php dan form.php.
   * List.php digunakan untuk melakukan perintah CRUD
   * Form.php digunakan untuk menambah dan mengedit data,
   * mendukung beberapa tipe field seperti input, textarea, radio dan combobox.
   * @access public
   * @param array
   */
   public function createView($request = [])
   {
      foreach ($request as $datas => $data) {

         $listTemplate = '<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title><?= $title ?></title>
      <!-- Main CSS -->
      <link rel="stylesheet" href="<?= base_url(\'bootstrap/css/bootstrap.min.css\') ?>">
      <link rel="stylesheet" href="<?= base_url(\'font-awesome/css/all.min.css\') ?>">
      <link rel="stylesheet" href="<?= base_url(\'datatables/css/dataTables.bootstrap4.min.css\') ?>">
      <link rel="stylesheet" href="<?= base_url(\'tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css\') ?>">
      <!-- .Main CSS -->
      <!-- Your Style -->
      <!-- .Your Style -->
   </head>
   <body>
      <main class="p-3" role="main">
         <div class="container">
            <div class="row justify-content-center">
               <div class="card">
                  <div class="card-header">
                     <h3><?= $title ?></h3>
                  </div>
                  <div class="card-body">
                     <p>
                        <div class="btn-group" role="group" aria-label="Action">
                           <button class="btn btn-sm btn-outline-danger float-left bulk-delete">Delete</button>
                           <button class="btn btn-sm btn-outline-primary float-left refresh" purpose="add">Refresh</button>
                        </div>
                        <button class="btn btn-sm btn-primary float-right form-action" purpose="add">Add</button>
                     </p>
                     <table id="datatable" class="table table-striped table-responsive table-bordered" cellspacing="0" style="width: 100%">
                        <thead class="text-center">
                           <tr>
                           <th style="width: 0px"><input type="checkbox" class="check-items"></th>';
         $i = 0;
         foreach ($data['non_pk'] as $nonPks => $nonPk) {
            if($i < 5){
               $listTemplate .= '
                           <th>'.humanize($nonPk->name).'</th>';
            }
            $i++;
         }
         $listTemplate .= '
                           <th style="width: 0px">#</th>
                           </tr>
                        </thead>
                     </table>
                  </div>
               </div>
            </div>
         </div>
      </main>

      <div class="modal modal-form" tabindex="-1" role="dialog">
         <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                  <div class="modal-header">
                     <h5 class="modal-title"></h5>
                     <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                     </button>
                  </div>
                  <div class="modal-body">
                  </div>
            </div>
         </div>
      </div>

   </body>
   <!-- Main Script -->
   <script src="<?= base_url(\'js/jquery.min.js\') ?>"></script>
   <script src="<?= base_url(\'js/moment.min.js\') ?>"></script>
   <script src="<?= base_url(\'bootstrap/js/popper.min.js\') ?>"></script>
   <script src="<?= base_url(\'bootstrap/js/bootstrap.min.js\') ?>"></script>
   <script src="<?= base_url(\'datatables/js/jquery.dataTables.min.js\') ?>"></script>
   <script src="<?= base_url(\'datatables/js/dataTables.bootstrap4.min.js\') ?>"></script>
   <script src="<?= base_url(\'tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js\') ?>"></script>
   <!-- .Main Script -->
   <!-- Your Magic -->
   <script>
   $(document).ready(function(){

      let h = "<?= $host ?>", t = $(\'#datatable\').DataTable({
            "processing": true,
            "serverSide": true,
            "ajax":
            {
               "url": h + "data",
               "type": "POST"
            },
            "columns": [
               { "data": "column_bulk", "searchable" : false, "orderable" : false },';
         $i = 0;
         foreach ($data['non_pk'] as $nonPks => $nonPk) {
            if($i < 5){
               $listTemplate .= '
               { "data": "'.$nonPk->name.'"},';
            }
            $i++;
         }
         $listTemplate .= '
               { "data": "column_action", "searchable" : false, "orderable" : false }
            ],
            "order": [[1, "DESC"]]
      });

      function fresh(){
         t.ajax.reload();
      }

      function save(url, data){
         $.ajax({
            type: \'POST\',
            url: url,
            data: data,
            cache: false,
            processData: false,
            contentType: false,
         }).done(function(){
            fresh();
            $(\'.modal-form\').modal(\'hide\');
         }).fail(function(res){
            $(\'.form-text\').remove()
            $(\'.is-invalid\').removeClass(\'is-invalid\')
            let err = jQuery.parseJSON(res.responseText)
            $.each(err.messages, function( selector, value ) {
               $(\'[for="\' + selector +\'"]\').after(\'<small class="form-text text-danger">\' + value + \'</small>\');
               $(\'[name="\' + selector +\'"]\').addClass(\'is-invalid\');
            });
         });
      }

      function drop(ids){
         let ok = confirm("Are you sure?");
         if (ok == true) {
            for (let i = 0; i < ids.length; i++) {
               $.ajax({
                  url: h + \'delete/\' + ids[i]
               }).fail(function(){
                  console.log(\'Data not found\');
               }).always(function() {
                  fresh();
               });
            }
         }
      }

      t.on(\'draw\', function(){
         $(\'.form-action\').on(\'click\', function(){
            let me = $(this), mb = $(\'.modal-form\'), m, u, u2, id = me.attr(\'item-id\'), t, p = me.attr(\'purpose\');
            if (p === "add") { t = "Add Data"; u = h + \'new\'; u2 = h + \'create\'; }
            else if (p === "edit") { t = "Edit Data"; u = h + \'edit/\' + id; u2 = h + \'update/\' + id; }
            else { t = "Detail Data"; u = h + \'show/\' + id; }

            $.ajax({
               type: "GET",
               url: u
            }).done(function(r){
               mb.find(\'.modal-title\').text(t);
               mb.find(\'.modal-body\').html(r);
               mb.modal(\'show\');
               lib();

               $(\'#form input:text, #form textarea\').first().focus();
               $(\'#form\').on(\'submit\', function(e){
                  e.preventDefault();
                  // let d = $(\'#form\').serialize();
                  let d = new FormData(this);
                  save(u2, d);
               });
            }).fail(function(){
               alert("Data not found");
            });

         });
      });

      $(\'.refresh\').on(\'click\', function(){ fresh() })

      $(\'.check-items\').on(\'click\', function(){
         $(\'input:checkbox\').not(this).prop(\'checked\', this.checked);
      });

      $(\'.bulk-delete\').on(\'click\', function(){
         let ids = [];
         $(".bulk-item").each(function(){
            if($(this).is(":checked")){
               ids.push($(this).val());
            }
         });
         if(ids.length){
            drop(ids);
         }
         else{
            alert("Please select items");
         }
      });

      function lib(){
         $.fn.datetimepicker.Constructor.Default = $.extend({}, $.fn.datetimepicker.Constructor.Default, {
            icons: {
               time: \'fas fa-clock\',
               date: \'fas fa-calendar\',
               up: \'fas fa-arrow-up\',
               down: \'fas fa-arrow-down\',
               previous: \'fas fa-chevron-left\',
               next: \'fas fa-chevron-right\',
               today: \'fas fa-calendar-check-o\',
               clear: \'fas fa-trash\',
               close: \'fas fa-times\'
            },
            minDate: \'1900-01-01 00:00\',
            maxDate: \'2155-12-31 23:59\',
            useCurrent: false
         });

         $(\'.year\').datetimepicker({
            viewMode: \'years\',
            format: \'YYYY\'

         });
         $(\'.datetime\').datetimepicker({
            viewMode: \'years\',
            format: \'YYYY-MM-DD HH:mm\'
         });
         $(\'.date\').datetimepicker({
            format: \'YYYY-MM-DD\'
         });
         $(\'.time\').datetimepicker({
            format: \'HH:mm\'
         });
         // Custom Library
         // .Custom Library
      }

   });
   </script>
   <!-- .Your Magic -->
</html>';

         if(!is_dir(APPPATH.'Views/'.$data['table'])){
            mkdir(APPPATH.'Views/'.$data['table']);
         }

         // make list
         $listFile = fopen(APPPATH.'Views/'.$data['table'].'/list.php','w')
            or $this->failServerError("Access to the path '".APPPATH."' is denied. Change your permision path to 777.");
         fwrite($listFile, $listTemplate);
         fclose($listFile);
         // .make list


         $formTemplate = '<form id="form" accept-charset="utf-8">';

         // grup kolom fk
         $groupFk = [];
         foreach ($data['fk'] as $fks => $fk) {
            $groupFk[] = $fk->column_name;
         }
         // .grup kolom fk

         foreach ($data['non_pk'] as $nonPks => $nonPk) {

            $formTemplate .= '
   <div class="form-group">';

            $formTemplate .= '
      <label for="'.$nonPk->name.'">'.humanize($nonPk->name).'</label>';

            // kondisi ada relasi
            // if (!empty($data['fk'])) {
            if (in_array($nonPk->name, $groupFk)) {
               // pecah fk
               foreach ($data['fk'] as $fks => $fk) {
                  if ($nonPk->name == $fk->column_name)
                     // type array (combobox)
                     $formTemplate .= '
      <select name="'.$nonPk->name.'" class="custom-select">
         <?php foreach($data_'.$fk->foreign_table_name.' as $'.plural($fk->foreign_table_name).' => $'.singular($fk->foreign_table_name).'): ?>
         <option value="<?= $'.singular($fk->foreign_table_name).'[\''.$fk->foreign_column_name.'\'] ?>" <?= !empty($data_'.$data['table'].'[\''.$nonPk->name.'\']) && $data_'.$data['table'].'[\''.$nonPk->name.'\'] == $'.singular($fk->foreign_table_name).'[\''.$fk->foreign_column_name.'\'] ? \'selected\' : \'\' ?>><?= $'.singular($fk->foreign_table_name).'[\''.$fk->foreign_column_name.'\'] ?></option>
         <?php endforeach ?>
      </select>';
                     // .type array (combobox)

               }
               // .pecah fk
            }
            // .kondisi ada relasi

            // kondisi tidak ada relasi
            else{
               // type text (textarea)
               if ($nonPk->type == 'tinytext' || $nonPk->type == 'text' || $nonPk->type == 'mediumtext' || $nonPk->type == 'longtext') {
                  $formTemplate .= '
      <textarea type="text" name="'.$nonPk->name.'" class="form-control" ><?= !empty($data_'.$data['table'].'[\''.$nonPk->name.'\']) ? $data_'.$data['table'].'[\''.$nonPk->name.'\'] : \'\' ?></textarea>';
               }
               // .type text (textarea)

               // type enum (radio)
               else if($nonPk->type == 'enum' || $nonPk->type == 'set'){
                  $radio = 1;
                  foreach ($nonPk->option_value as $enum) {
                     $formTemplate .= '
      <div class="form-check">
         <input class="form-check-input" type="radio" id="'.$nonPk->name.$radio.'" name="'.$nonPk->name.'" value="'.$enum.'" <?= !empty($data_'.$data['table'].'[\''.$nonPk->name.'\']) && $data_'.$data['table'].'[\''.$nonPk->name.'\'] == \''.$enum.'\' ? \'checked\' : \'\' ?> />
         <label class="form-check-label" for="'.$nonPk->name.$radio.'">'.humanize($enum).'</label>
      </div>';
                     $radio++;
                  }
               }
               // .type enum (radio)

               // type time (time)
               else if($nonPk->type == 'time'){
                  $formTemplate .= '
      <input type="text" name="'.$nonPk->name.'" value="<?= !empty($data_'.$data['table'].'[\''.$nonPk->name.'\']) ? $data_'.$data['table'].'[\''.$nonPk->name.'\'] : \'\' ?>" class="form-control time" id="'.$nonPk->name.'" data-toggle="datetimepicker" data-target="#'.$nonPk->name.'" />';
               }
               // .type time (time)

               // type date (date)
               else if($nonPk->type == 'date'){
                  $formTemplate .= '
      <input type="text" name="'.$nonPk->name.'" value="<?= !empty($data_'.$data['table'].'[\''.$nonPk->name.'\']) ? $data_'.$data['table'].'[\''.$nonPk->name.'\'] : \'\' ?>" class="form-control date" id="'.$nonPk->name.'" data-toggle="datetimepicker" data-target="#'.$nonPk->name.'" />';
               }
               // .type date (date)

               // type datetime (datetime)
               else if($nonPk->type == 'datetime'){
                  $formTemplate .= '
      <input type="text" name="'.$nonPk->name.'" value="<?= !empty($data_'.$data['table'].'[\''.$nonPk->name.'\']) ? $data_'.$data['table'].'[\''.$nonPk->name.'\'] : \'\' ?>" class="form-control datetime" id="'.$nonPk->name.'" data-toggle="datetimepicker" data-target="#'.$nonPk->name.'" />';
               }
               // .type datetime (datetime)

               // type year (year)
               else if($nonPk->type == 'year'){
                  $formTemplate .= '
      <input type="text" name="'.$nonPk->name.'" value="<?= !empty($data_'.$data['table'].'[\''.$nonPk->name.'\']) ? $data_'.$data['table'].'[\''.$nonPk->name.'\'] : \'\' ?>" class="form-control year" id="'.$nonPk->name.'" data-toggle="datetimepicker" data-target="#'.$nonPk->name.'" />';
               }
               // .type year (year)

               // type blob (file)
               else if($nonPk->type == 'tinyblob' || $nonPk->type == 'blob' || $nonPk->type == 'mediumblob' || $nonPk->type == 'longblob'){
                  $formTemplate .= '
      <input type="file"  name="'.$nonPk->name.'" class="form-control" id="file_'.$nonPk->name.'">';
               }
               // .type blob (file)

               // type string (text)
               else{
                  $formTemplate .= '
      <input type="text" name="'.$nonPk->name.'" value="<?= !empty($data_'.$data['table'].'[\''.$nonPk->name.'\']) ? $data_'.$data['table'].'[\''.$nonPk->name.'\'] : \'\' ?>" class="form-control" />';
               }
               // .type string (text)
            }
            // .kondisi tidak ada relasi
               $formTemplate .= '
   </div>';
         }

         $formTemplate .= '
   <div class="form-group">
      <button type="submit" class="btn btn-primary">Save</button>
      <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      <label for="error"></label>
   </div>
</form>';

         // make form
         $formFile = fopen(APPPATH.'Views/'.$data['table'].'/form.php','w')
            or $this->failServerError("Access to the path '".APPPATH."' is denied. Change your permision path to 777.");
         fwrite($formFile, $formTemplate);
         fclose($formFile);
         // .make form
      }
   }

   /**
   * Membuat Controller
   *
   * Terdapat aturan validasi request.
   * Terdapat method CRUD. Response berupa
   * data json dan html.
   * @access public
   * @param array
   */
   public function createController($request = [])
   {
     foreach ($request as $datas => $data) {
         $controllerTemplate = '<?php
namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\API\ResponseTrait;';

         $controllerTemplate .= '
use App\Models\\'.$data['model'].';';

         if (!empty($data['other_model'])) {
            foreach ($data['other_model'] as $otherModel) {
               $controllerTemplate .= '
use App\Models\\'.$otherModel.';';
            }
         }


         $controllerTemplate .= '
class '.$data['controller'].' extends Controller{
   use ResponseTrait;

   public function __construct() {';
         $controllerTemplate .= '
      $this->'.$data['model'].' = new '.$data['model'].';';

         if (!empty($data['other_model'])) {
            foreach ($data['other_model'] as $otherModel) {
               $controllerTemplate .= '
      $this->'.$otherModel.' = new '.$otherModel.';';
            }
         }


         $controllerTemplate .= '

      $tableTemplate = [
         \'table_open\' => \'<table class="table table-responsive table-striped">\',
         \'tbody_open\'         => \'<tbody>\',
         \'tbody_close\'        => \'</tbody>\',
         \'table_close\'        => \'</table>\'
      ];
      $this->table = new \CodeIgniter\View\Table($tableTemplate);
      $this->validation = \Config\Services::validation();
   }

   function index(){
      $data = [
         \'title\' => \'Data '.humanize($data['table']).'\',
         \'host\' => site_url(\''.strtolower($data['controller']).'/\')
      ];
      echo view(\''.$data['table'].'/list\', $data);
   }

   public function data(){
      try
      {
         $request = esc($this->request->getPost());
         $search = $request[\'search\'][\'value\'];
         $limit = $request[\'length\'];
         $start = $request[\'start\'];

         $orderIndex = $request[\'order\'][0][\'column\'];
         $orderFields = $request[\'columns\'][$orderIndex][\'data\'];
         $orderDir = $request[\'order\'][0][\'dir\'];

         $recordsTotal = $this->'.$data['model'].'->countTotal();
         $data = $this->'.$data['model'].'->filter($search, $limit, $start, $orderFields, $orderDir);
         $recordsFiltered = $this->'.$data['model'].'->countFilter($search);

         $callback = [
            \'draw\' => $request[\'draw\'],
            \'recordsTotal\' => $recordsTotal,
            \'recordsFiltered\' => $recordsFiltered,
            \'data\' => $data
         ];

         return $this->respond($callback);
      }
      catch (\Exception $e)
      {
         // return $this->failServerError($e->getMessage());
         return $this->failServerError(\'Sorry, an error occurred. Please contact the administrator.\');
      }

   }

   public function new()
   {
      $data = [';
         if (!empty($data['other_model'])) {
            $om = 0;
            foreach ($data['other_model'] as $otherModel) {
               $controllerTemplate .= '
         \'data_'.$data['fk'][$om]->foreign_table_name.'\' => $this->'.$otherModel.'->findAll(),';
               $om++;
            }
         }


         $controllerTemplate .= '];

      echo view(\''.$data['table'].'/form\', $data);
   }

   public function create()
   {
      $request = esc($this->request->getPost());
      $this->rules();

      if ($this->validation->run($request) != TRUE) {
         return $this->respond([
            \'status\' => 400,
            \'error\' => 400,
            \'messages\' => $this->validation->getErrors()
         ], 400);

      } else {
         try
         {';

         foreach ($data['non_pk'] as $nonPks => $nonPk) {
            if ($nonPk->type == 'tinyblob' || $nonPk->type == 'blob' || $nonPk->type == 'mediumblob' || $nonPk->type == 'longblob') {
               $controllerTemplate .= '
            $contents=[];
            if($getFiles = $this->request->getFiles()){
               foreach($getFiles as $files => $file)
               {
                  if ($file->isValid() && ! $file->hasMoved())
                  {
                     $newName = $file->getRandomName();
                     $contents[] = $newName;
                     $file->move(WRITEPATH.\'uploads\', $newName);
                     $request[$files] = base64_encode(file_get_contents(WRITEPATH.\'uploads/\'.$newName));
                     $request[$files.\'_media_type\'] = $file->getClientMimeType();
                  }
               }
            }';
               break;
            }
         }

         $controllerTemplate .= '
            $insert = $this->'.$data['model'].'->insert($request);

            if ($insert)
            {';

         foreach ($data['non_pk'] as $nonPks => $nonPk) {
            if ($nonPk->type == 'tinyblob' || $nonPk->type == 'blob' || $nonPk->type == 'mediumblob' || $nonPk->type == 'longblob') {
               $controllerTemplate .= '
               foreach ($contents as $content) {
                  unlink(WRITEPATH.\'uploads/\'.$content);
               }
               ';
               break;
            }
         }

         $controllerTemplate .= '
               return $this->respondCreated([
                  \'status\' => 201,
                  \'message\' => \'Data created.\'
               ]);
            }
            else
            {
               return $this->fail($this->'.$data['model'].'->errors());
            }
         }
         catch (\Exception $e)
         {
            // return $this->failServerError($e->getMessage());
            return $this->failServerError(\'Sorry, an error occurred. Please contact the administrator.\');
         }
      }

   }

   public function show($id = null)
   {
      try
      {
         $data = $this->'.$data['model'];

         if(!empty($data['fk'])){
            foreach ($data['fk'] as $fks => $fk) {
                     $controllerTemplate .= '->join(\''.$fk->foreign_table_name.'\', \''.$fk->foreign_table_name.'.'.$fk->foreign_column_name.' = '.$fk->table_name.'.'.$fk->column_name.'\')
                     ';
            }
         }

         $controllerTemplate .= '->find($id);
         if ($data)
         {
            // Secara bawaan menampilkan data dari tabel utama saja.';

         foreach ($data['non_pk'] as $nonPks => $nonPk) {
            if ($nonPk->type == 'tinyblob' || $nonPk->type == 'blob' || $nonPk->type == 'mediumblob' || $nonPk->type == 'longblob') {
               $controllerTemplate .= '
            $this->table->addRow([\''.humanize($nonPk->name).'\', \':\', !empty($data[\''.$nonPk->name.'\']) ? \'<div class="card card-body"><object style="width:100%;height:auto" data="data:\'.$data[\''.$nonPk->name.'_media_type\'].\';base64,\'.$data[\''.$nonPk->name.'\'].\'"></object></div>\' : \'\']);';
            }
            else{
               $controllerTemplate .= '
            $this->table->addRow([\''.humanize($nonPk->name).'\', \':\', $data[\''.$nonPk->name.'\']]);';
            }
         }

         $controllerTemplate .= '
            return $this->respond($this->table->generate());
         }
         else{
            return $this->failNotFound();
         }
      }
      catch (\Exception $e)
      {
         // return $this->failServerError($e->getMessage());
         return $this->failServerError(\'Sorry, an error occurred. Please contact the administrator.\');
      }

   }

   public function edit($id = null)
   {
      try
      {
         $data = $this->'.$data['model'].'->find($id);

         if ($data)
         {
            $data = [';
            if (!empty($data['other_model'])) {
               $om = 0;
               foreach ($data['other_model'] as $otherModel) {
                  $controllerTemplate .= '
               \'data_'.$data['fk'][$om]->foreign_table_name.'\' => $this->'.$otherModel.'->findAll(),';
                  $om++;
               }
            }

            $controllerTemplate .= '
               \'data_'.$data['table'].'\' => $data
            ];

            echo view(\''.$data['table'].'/form\', $data);
         }
         else
         {
            return $this->failNotFound();
         }
      }
      catch (\Exception $e)
      {
         // return $this->failServerError($e->getMessage());
         return $this->failServerError(\'Sorry, an error occurred. Please contact the administrator.\');
      }

   }

   public function update($id = null)
   {
      $request = esc($this->request->getPost());
      $this->rules();

      if ($this->validation->run($request) != TRUE) {
         return $this->respond([
            \'status\' => 400,
            \'error\' => 400,
            \'messages\' => $this->validation->getErrors()
         ], 400);

      } else {
         try
         {';

         foreach ($data['non_pk'] as $nonPks => $nonPk) {
            if ($nonPk->type == 'tinyblob' || $nonPk->type == 'blob' || $nonPk->type == 'mediumblob' || $nonPk->type == 'longblob') {
               $controllerTemplate .= '
            $contents=[];
            if($getFiles = $this->request->getFiles()){
               foreach($getFiles as $files => $file)
               {
                  if ($file->isValid() && ! $file->hasMoved())
                  {
                     $newName = $file->getRandomName();
                     $contents[] = $newName;
                     $file->move(WRITEPATH.\'uploads\', $newName);
                     $request[$files] = base64_encode(file_get_contents(WRITEPATH.\'uploads/\'.$newName));
                     $request[$files.\'_media_type\'] = $file->getClientMimeType();
                  }
               }
            }';
               break;
            }
         }

         $controllerTemplate .= '
            $update = $this->'.$data['model'].'->update($id, $request);

            if ($update)
            {';

         foreach ($data['non_pk'] as $nonPks => $nonPk) {
            if ($nonPk->type == 'tinyblob' || $nonPk->type == 'blob' || $nonPk->type == 'mediumblob' || $nonPk->type == 'longblob') {
               $controllerTemplate .= '
               foreach ($contents as $content) {
                  unlink(WRITEPATH.\'uploads/\'.$content);
               }';
               break;
            }
         }

         $controllerTemplate .= '
               return $this->respondNoContent(\'Data updated\');
            }
            else {
               return $this->fail($this->'.$data['model'].'->errors());
            }
         }
         catch (\Exception $e)
         {
            // return $this->failServerError($e->getMessage());
            return $this->failServerError(\'Sorry, an error occurred. Please contact the administrator.\');
         }
      }
   }

   public function delete($id = null)
   {
      try
      {
         $data = $this->'.$data['model'].'->find($id);
         if ($data)
         {
            $this->'.$data['model'].'->delete($id);
            return $this->respondDeleted([
               \'status\' => 200,
               \'message\' => \'Data deleted.\'
            ]);
         }
         else
         {
            return $this->failNotFound();
         }
      }
      catch (\Exception $e)
      {
         // return $this->failServerError($e->getMessage());
         return $this->failServerError(\'Sorry, an error occurred. Please contact the administrator.\');
      }
   }

   private function rules(){
      $this->validation->setRules([';
      foreach ($data['non_pk'] as $nonPks => $nonPk) {
         $controllerTemplate .= '
         \''.$nonPk->name.'\' => [
            \'label\' => \''.humanize($nonPk->name).'\',
            \'rules\' => \'';

         if ($nonPk->type == 'tinyint' || $nonPk->type == 'smallint' || $nonPk->type == 'mediumint' || $nonPk->type == 'int' || $nonPk->type == 'bigint'){
            $controllerTemplate .= 'required|numeric';
         }

         else if ($nonPk->type == 'float' || $nonPk->type == 'double' || $nonPk->type == 'real' || $nonPk->type == 'decimal' || $nonPk->type == 'numeric'){
            $controllerTemplate .= 'required|decimal';
         }

         else if ($nonPk->type == 'time'){
            $controllerTemplate .= 'required|valid_date[H:i]';
         }

         else if ($nonPk->type == 'date'){
            $controllerTemplate .= 'required|valid_date[Y-m-d]';
         }

         else if ($nonPk->type == 'year'){
            $controllerTemplate .= 'required|valid_date[Y]';
         }

         else if ($nonPk->type == 'datetime'){
            $controllerTemplate .= 'required|valid_date[Y-m-d H:i]';
         }

         else if ($nonPk->type == 'enum'){
            $controllerTemplate .= 'required|in_list[';
            $e = 1;
            foreach ($nonPk->option_value as $enum) {
               $controllerTemplate .= $enum;
               if ($e < count($nonPk->option_value)) {
                  $controllerTemplate .= ', ';
               }
               $e++;
            }
            $controllerTemplate .= ']';
         }

         else if ($nonPk->type == 'tinyblob' || $nonPk->type == 'blob' || $nonPk->type == 'mediumblob' || $nonPk->type == 'longblob'){
            $controllerTemplate .= 'ext_in['.$nonPk->name.',png,jpg,pdf]|mime_in['.$nonPk->name.',image/png,image/jpeg,application/pdf]|max_size['.$nonPk->name.',2048]';
         }

         else{
            $controllerTemplate .= 'required|string';
         }

         if ($nonPk->max_length != null){
            $controllerTemplate .= '|max_length['.$nonPk->max_length.']';
         }


         $controllerTemplate .= '\'
         ],';
      }


         $controllerTemplate .= '
      ]);
   }

}';

         // make controller
         $controllerFile = fopen(APPPATH.'Controllers/'.$data['controller'].'.php','w')
            or $this->failServerError("Access to the path '".APPPATH."' is denied. Change your permision path to 777.");
         fwrite($controllerFile, $controllerTemplate);
         fclose($controllerFile);
         // .make controller
     }
   }

}
