# BCODE-CRUD-GENERATOR

BCODE CRUD GENERATOR adalah generator sederhana khusus untuk CodeIgniter 4, mempermudah dalam merancang perintah umum seperti membuat, membaca, mengubah dan menghapus data.

## Fitur

- Bootstrap 4
- AJAX CRUD
- Server-side datatable
- Kustomisasi penamaan Model dan Controller
- Otomatisasi elemen bidang berdasarkan tipe data (string, numerik dan tanggal) kolom pada tabel seperti:

| Elemen Bidang | Tipe Data |
| ------ | ------ |
| `textarea` | `tinytext`, `text`, `mediumtext`, `longtext` |
| `radio` | `enum` |
| `select` | *untuk relasi antar tabel* |
| `input` file | `tinyblob`, `blob`, `mediumblob`, `longblob` |
| `input` text | *untuk tipe data kolom lainnya* |

## Instalasi

1. Kloning repositori

```bash
git clone https://github.com/banguncode/BCODE-CRUD-GENERATOR.git
```

2. Salin isi folder `public` dan `app` ke dalam direktori root proyek anda 

3. Salin file `env` dan ganti nama menjadi `.env`

4. Hapus tanda komentar pada bagian *ENVIRONMENT* dan ubah production menjadi development

```environment 
CI_ENVIRONMENT = development
```

5. Hapus tanda komentar pada bagian *DATABASE* kemudian sesuaikan konfigurasi database

```environment 
database.default.hostname = localhost
database.default.database = ci4
database.default.username = root
database.default.password = root
database.default.DBDriver = MySQLi 
```

## Penggunaan

1. Jalankan server

```bash
start http://localhost:8080; php spark serve
```

2. Kunjungi `http://localhost:8080/bcode`

3. Pada bilah kerja **Alat** pilih tabel yang ingin di-generate, sesuaikan nama Model dan Controller jika diperlukan

4. Pada bilah kerja **Log** menampilkan pesan hasil generator

5. Selesai

## Colek

- [codeigniter4/CodeIgniter4](https://github.com/codeigniter4/CodeIgniter4)
- [twbs/bootstrap](https://github.com/twbs/bootstrap)
- [juery/jquery](https://github.com/jquery/jquery)
- [DataTables/DataTables](https://github.com/DataTables/DataTables)

## Kontribusi

Pull requests dipersilakan. Untuk perubahan besar, harap buka issue terlebih dahulu untuk membahas apa yang ingin diubah.

## Donasi

Jika berkenan bantu saya membeli kopi, rokok dan gorengan.

> PayPal
**rum.haidar@hotmail.com**

> BRI
**421401001171502**
a/n RUM HAIDAR FAUZAN

> BCA
**0600873041**
a/n RUM HAIDAR FAUZAN

> JENIUS
**90013536859**
a/n RUM HAIDAR FAUZAN

> OVO/GOPAY/LinkAja
**089653129960**
a/n RUM HAIDAR FAUZAN

## License
[GNU GPLv3 ](https://choosealicense.com/licenses/gpl-3.0/)
