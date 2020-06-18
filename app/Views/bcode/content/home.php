<div class="row">
	<div class="col-12">
		<div class="accordion pb-3" id="accordionPengantar">
			<div class="card">
				<div class="card-header" id="headingOne">
					<h2 class="mb-0">
						<button class="btn btn-link btn-block text-left text-dark" type="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
						<h3>Pengantar</h3>
						</button>
					</h2>
				</div>

				<div id="collapseOne" class="collapse" aria-labelledby="headingOne" data-parent="#accordionPengantar">
					<div class="card-body">
						<h5>Deskripsi</h5>
						<p>BCODE CRUD GENERATOR adalah generator sederhana khusus untuk CodeIgniter 4, mempermudah dalam merancang perintah umum seperti membuat, membaca, mengubah dan menghapus data. Didukung dengan beberapa fitur dan sumber daya yaitu sebagai berikut:</p>
						<ul>
							<li>Bootstrap 4</li>
							<li>AJAX CRUD</li>
							<li>Server-side datatable</li>
							<li>Kustomisasi penamaan Model dan Controller</li>
							<li>Otomatisasi elemen bidang berdasarkan tipe data (string, numerik dan tanggal) kolom pada tabel seperti:
								<ul>
								<li>Elemen bidang <code>textarea</code> (<code>tinytext</code>, <code>text</code>, <code>mediumtext</code>, <code>longtext</code>)</li>
								<li>Elemen bidang <code>radio</code> (<code>enum</code>)</li>
								<li>Elemen bidang <code>select</code> (untuk relasi antar tabel)</li>
								<li>Elemen bidang <code>input</code> bertipe <code>file</code> (<code>tinyblob</code>, <code>blob</code>, <code>mediumblob</code>, <code>longblob</code>)</li>
								<li>Elemen bidang <code>input</code> bertipe <code>text</code> (untuk tipe data kolom lainnya)</li>
								</ul>
							</li>
						</ul>

						<h5>Persiapan & Penggunaan</h5>
						<ol>
							<li>Salin file <span class="badge badge-secondary">env</span> dan ganti nama menjadi <span class="badge badge-secondary">.env</span></li>
							<li>Hapus tanda komentar pada bagian <i>ENVIRONMENT</i> dan ubah <code>production</code> menjadi <code>development</code></li>
							<code>
								CI_ENVIRONMENT = development
							</code>
							<li>Hapus tanda komentar pada bagian <i>DATABASE</i> kemudian sesuaikan konfigurasi database</li>
							<code>
								database.default.hostname = localhost<br>
								database.default.database = ci4<br>
								database.default.username = root<br>
								database.default.password = root<br>
								database.default.DBDriver = MySQLi
							</code>
							<li>Pada bilah kerja <strong>Alat</strong> pilih tabel yang ingin di-generate, sesuaikan nama Model dan Controller jika diperlukan</li>
							<li>Pada bilah kerja <strong>Log</strong> menampilkan pesan hasil generator</li>
						</ol>
						<p><a class="btn btn-info" href="https://github.com/banguncode/BCODE-CRUD-GENERATOR" target="_blank" role="button">Lebih lanjut &raquo;</a></p>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="col-6">
		<div class="card">
			<div class="card-header">
				<h3>Alat</h3>
			</div>
			<div class="card-body">
				<form id="tools-form">
					<div class="form-group">
						<label for="">Tabel</label>
						<select type="text" name="table[]" id="tables" class="form-control select2" multiple="multiple">
							<?php
								foreach($tables as $value){
									?>
									<option value="<?= $value ?>"><?= $value ?></option>
									<?php
								}
							?>
						</select>
                  <div class="form-check">
                     <input type="checkbox" class="form-check-input" id="select-all-tables">
                     <label class="form-check-label" for="select-all-tables">Pilih semua</label>
                  </div>
					</div>
					<div class="form-group">
						<label for="">Penamaan Controller</label>
						<input type="text" name="controller" class="form-control" value="Bcode" placeholder="Dafault: Bcode">
					</div>
					<div class="form-group">
						<label for="">Penamaan Model</label>
						<input type="text" name="model" class="form-control" value="BcodeModel" placeholder="Default: BcodeModel">
					</div>
					<div class="alert alert-info" role="alert">
						<h4 class="alert-heading">Penting!</h4>
						<small><ul>
							<li>awali penamaan Model dan Controller dengan huruf kapital.</li>
							<li>wajib mencantumkan kata <b>Bcode</b> agar dapat mengidentifikasi penamaan Model dan Controller
							<br>Contoh: Bcode/BcodePrefix/Bcode_Prefix/Prefix_Bcode/dsb.</li>
							<li>imbuhan Prefix dapat disesuaikan jika dibutuhkan (opsional).</li>
						</ul></small>
					</div>
					<div class="form-group">
                  <span class="text-muted">Segera hadir</span>
						<div class="form-check form-check-inline">
							<input name="export[]" class="form-check-input" type="checkbox" id="inlineCheckbox1" value="pdf" disabled>
							<label class="form-check-label" for="inlineCheckbox1">Pdf</label>
						</div>
						<div class="form-check form-check-inline">
							<input name="export[]" class="form-check-input" type="checkbox" id="inlineCheckbox2" value="excel" disabled>
							<label class="form-check-label" for="inlineCheckbox2">Excel</label>
						</div>
						<div class="form-check form-check-inline">
							<input name="export[]" class="form-check-input" type="checkbox" id="inlineCheckbox3" value="word" disabled>
							<label class="form-check-label" for="inlineCheckbox3">Word</label>
						</div>
					</div>
					<div class="form-group">
						<input type="reset" id="tools-reset" class="btn btn-sm btn-secondary" value="Atur Ulang">
					</div>
				</form>
			</div>
			<div class="card-footer">
            <small class="form-text text-danger font-italic">* Perhatian! dengan menekan tombol <strong>Hasilkan</strong> maka proses generate dilakukan dan akan menimpa file yang sama jika tersedia.</small>
				<button class="btn btn-primary" id="tools-generate">Hasilkan</button>
			</div>
		</div>
	</div>

	<div class="col-6">
		<div class="card">
			<div class="card-header">
				<h3>Log</h3>
			</div>

			<div class="card-body">
				<div class="overflow-auto p-3 mb-3 mb-md-0 mr-md-3 bg-light" style="height: 300px;">
					<code id="log"></code>
				</div>
			</div>

			<div class="card-footer">
				<button class="btn btn-secondary" id="log-clear">Bersihkan</button>
			</div>
		</div>
	</div>

</div>