<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
	<meta name="description" content="BCODE CRUD GENERATOR adalah paket yang digunakan untuk membuat CRUD Codeigniter 4 dengan cepat dan mudah">
	<meta name="author" content="Rum Haidar Fauzan">
	<title>BCODE CRUD GENERATOR</title>
	<link rel="stylesheet" href="<?= base_url('bootstrap/css/bootstrap.min.css') ?>">
	<link rel="stylesheet" href="<?= base_url('select2/css/select2.min.css') ?>">
</head>
<body class="mb-5">
	<main class="p-3" role="main">
		<div class="container">
				<?= view($content) ?>
		</div>
	</main>

	<footer class="footer py-3 bg-light" style="bottom: 0; position: fixed; width: 100%;">
		<div class="container">
			<span class="text-muted">&copy; 2020 dibuat dengan &hearts; oleh Bangun Code</span>
		</div>
	</footer>
</body>
<script src="<?= base_url('js/jquery.min.js') ?>"></script>
<script src="<?= base_url('bootstrap/js/popper.min.js') ?>"></script>
<script src="<?= base_url('bootstrap/js/bootstrap.min.js') ?>"></script>
<script src="<?= base_url('select2/js/select2.min.js') ?>"></script>
<script>
	$(document).ready(function(){
		$('.select2').select2({placeholder: "-- Pilih Tabel --", allowClear: true});
      $("#select-all-tables").click(function(){
         if($("#select-all-tables").is(':checked')){
            $("#tables > option").prop("selected", true);
            $("#tables").trigger("change");
         }
         else{
            $('#tables').val(null).trigger('change');
         }
      });

		var $log = $('#log')

		$('#tools-reset').on('click', function(){
			$('#tables').val(null).trigger('change');
		});

		$('#log-clear').on('click', function(){
			$log.empty()
		});

		$('#tools-generate').on('click', function(){
			var msgO = "========== MULAI ==========<br>";
			var msgC = "========= SELESAI =========<br>";
			var now = new Date($.now());
			var logTime = "[" + now + "]<br>";
         var data = $('#tools-form').serialize();

			var request = $.ajax({
				url: "<?= site_url('bcode/generate') ?>",
				method: "POST",
				data: data,
				beforeSend: function(){
					$log.append( msgO + logTime);
				},
			}).done(function( res ) {
            $log.append( "SUKSES: " + res + "<br>" + msgC);
			}).fail(function( res) {
            var err = jQuery.parseJSON(res.responseText)
            $.each(err.messages, function (index, value) {
               $log.append( "GALAT: " + value + "<br>");
            });

            $log.append( msgC );

			});
		});

	})
</script>
</html>
