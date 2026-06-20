# API Holding — SI DBM

Dokumentasi endpoint laporan keuangan tenant untuk di-integrasikan ke **aplikasi holding pusat**.

Tujuan: client (holding) hanya merender JSON → view. Tidak ada kalkulasi sisi klien.

---

## Daftar Isi

1. [Autentikasi](#1-autentikasi)
2. [Konsep Umum](#2-konsep-umum)
3. [Periode & Tanggal Kondisi](#3-periode--tanggal-kondisi)
4. [Endpoint](#4-endpoint)
   - [4.1 Neraca](#41-neraca)
   - [4.2 Laba Rugi](#42-laba-rugi)
   - [4.3 Arus Kas](#43-arus-kas)
   - [4.4 Perubahan Ekuitas](#44-perubahan-ekuitas)
   - [4.5 CALK (Bagian C)](#45-calk-bagian-c)
5. [Aturan Rendering](#5-aturan-rendering)
6. [Contoh Kode Klien (PHP)](#6-contoh-kode-klien-php)

---

## 1. Autentikasi

Setiap request harus membawa **dua header**:

| Header              | Nilai                                | Sumber                                                              |
|---------------------|--------------------------------------|---------------------------------------------------------------------|
| `X-Holding-Token`   | `api_secret` dari master License     | `licenses.api_secret` (lihat menu Master → License)                 |
| `X-Holding-Tenant`  | Slug tenant (`web_kec` atau `web_alternatif`) | `kecamatan.web_kec` atau `kecamatan.web_alternatif`     |

### Cara mendapatkan kredensial

1. Login ke **Master SIDBM** (`/master`).
2. Buka menu **License** di sidebar.
3. **Tambah License** untuk kecamatan target:
   - Pilih kecamatan dari dropdown.
   - Isi `API Secret` dengan token yang diberikan aplikasi holding pusat (bukan di-generate lokal).
   - `is_active` aktifkan.
   - `expired_at` kosongkan jika tidak ada batas waktu.
4. Kirim `X-Holding-Token` = nilai `api_secret` tersebut.
5. Kirim `X-Holding-Tenant` = `web_kec` atau `web_alternatif` dari tabel `kecamatan` untuk tenant tsb.

### Response bila gagal auth

| HTTP | Arti                                          |
|------|-----------------------------------------------|
| 401  | Token tidak cocok, tenant tidak ditemukan, atau license non-aktif / expired |

---

## 2. Konsep Umum

### Struktur JSON universal

Setiap response berhasil:

```json
{
  "success": true,
  "laporan": "<nama laporan>",
  "kecamatan": "<nama_kec>",
  "periode": { "...": "..." },
  "ringkasan": { "...": "..." },
  "data": [ /* atau objek, lihat per endpoint */ ]
}
```

### Tipe data saldo

- Semua angka saldo = **float** (rupiah, tanpa format).
- Tanda: debit (+) / kredit (−) **sesuai konvensi akuntansi tenant** (`lev1` 1=Aset debit, 2/3=Liab/Ekuitas kredit, 4=Pendapatan, 5=Beban).
- Untuk ditampilkan: format dengan `number_format($n, 2)`. Tidak ada simbol Rp / koma pemisah desimal di JSON.

### Aturan `lev1`

| lev1 | Nama umum | Posisi normal |
|------|-----------|---------------|
| `1`  | Aset      | Debit         |
| `2`  | Liabilitas| Kredit        |
| `3`  | Ekuitas   | Kredit        |
| `4`  | Pendapatan| Kredit        |
| `5`  | Beban     | Debit         |

Endpoint **hanya mengembalikan `lev1 <= 3`** (Neraca, Perubahan Ekuitas, CALK). Laba Rugi pakai `lev1` 4/5.

### Special case `3.2.02.01` (Laba Rugi Tahun Berjalan)

Rekening `3.2.02.01` adalah akun **koreksi ekuitas** yang diisi otomatis dari hasil **Laba Rugi**. Untuk laporan dengan `tgl_kondisi`, saldo rekening ini **di-override** dengan nilai `laba_rugi(tgl_kondisi)`.

Endpoint yang memakai aturan ini: **Neraca**, **Perubahan Ekuitas**, **CALK Bagian C**.

---

## 3. Periode & Tanggal Kondisi

Semua endpoint menerima query:

| Param   | Wajib | Tipe    | Default                                   | Contoh         |
|---------|-------|---------|-------------------------------------------|----------------|
| `tahun` | Ya    | int     | —                                         | `2025`         |
| `bulan` | Tidak | int 1-12| `12` (tahunan)                            | `6`            |
| `hari`  | Tidak | int 1-31| Hari terakhir bulan tsb (atau `31` des)   | `30`           |
| `semester` (khusus arus-kas) | Tidak | `1`/`2` | — | `1` (Sem I) / `2` (Sem II) |

### Aturan turun `tgl_kondisi`

- `bulan` kosong → tahunan → `bulan=12`, `hari=31` (atau `tahun-12-31`).
- `hari` kosong → akhir bulan `tahun-bulan`.
- `tgl_kondisi` final berformat `YYYY-MM-DD`. Contoh: `tahun=2025&bulan=6&hari=30` → `2025-06-30`.

### Label periode

Tiap response menyertakan `periode.sub_judul` (string siap tampil):

| Mode           | Contoh sub_judul                                       |
|----------------|--------------------------------------------------------|
| Bulanan        | `Bulan Juni 2025`                                      |
| Tahunan        | `Tahun 2025`                                           |
| Semester I     | `Semester I Tahun 2025`                                |
| Semester II    | `Semester II Tahun 2025`                               |
| Laba Rugi (bulanan) | `Periode 01 Januari 2025 S.D 30 Juni 2025`        |
| Laba Rugi (tahunan) | `Tahun 2025`                                       |
| Neraca         | `Per 30 Juni 2025`                                     |

---

## 4. Endpoint

Base URL: `https://<host-tenant>/api/v1/holding/laporan`

### 4.1 Neraca

```
GET /neraca?tahun=2025&bulan=6&hari=30
```

#### Response

```json
{
  "success": true,
  "laporan": "Neraca",
  "kecamatan": "Kecamatan ABC",
  "tgl_kondisi": "2025-06-30",
  "sub_judul": "Per 30 Juni 2025",
  "ringkasan": {
    "total_aset": 150000000.0,
    "total_liabilitas_ekuitas": 150000000.0,
    "selisih": 0.0
  },
  "data": [
    {
      "kode_akun": "1",
      "nama_akun": "Aset",
      "lev1": "1",
      "saldo": 150000000.0,
      "akun2": [
        {
          "kode_akun": "1.1",
          "nama_akun": "Aset Lancar",
          "saldo": 80000000.0,
          "akun3": [
            { "kode_akun": "1.1.01", "nama_akun": "Kas", "saldo": 50000000.0 },
            { "kode_akun": "1.1.02", "nama_akun": "Bank", "saldo": 30000000.0 }
          ]
        }
      ]
    }
  ]
}
```

#### Cara render (mengikuti view tenant)

| Baris    | Sumber                               | Style         |
|----------|--------------------------------------|---------------|
| Header   | `data[]` lev1 nama_akun              | Bold          |
| Subtotal | `akun2.saldo`                        | Indent 1      |
| Rincian  | `akun3[].saldo`                      | Indent 2      |
| **Jumlah {lev1 nama}** | `data[].saldo`             | Bold, border  |
| **Jumlah Liabilitas + Ekuitas** | `ringkasan.total_liabilitas_ekuitas` | Bold, border |

> **Catatan:** `rekening` (level 4) **tidak** di-include di Neraca. View tenant hanya menghitung per-rekening secara internal lalu agregat ke `akun3.saldo`.

---

### 4.2 Laba Rugi

```
GET /laba-rugi?tahun=2025&bulan=6&hari=30
```

#### Response

```json
{
  "success": true,
  "laporan": "Laba Rugi",
  "kecamatan": "Kecamatan ABC",
  "periode": {
    "jenis": "Bulanan",
    "tgl_kondisi": "2025-06-30",
    "sub_judul": "Periode 01 Januari 2025 S.D 30 Juni 2025"
  },
  "ringkasan": {
    "pendapatan": 50000000.0,
    "beban": 30000000.0,
    "pendapatan_non_ops": 0.0,
    "beban_non_ops": 0.0,
    "lr_operasional": {
      "s_d_bulan_lalu": 10000000.0,
      "periode_ini": 5000000.0,
      "s_d_sekarang": 15000000.0
    },
    "lr_non_operasional": {
      "s_d_bulan_lalu": 0.0,
      "periode_ini": 0.0,
      "s_d_sekarang": 0.0
    },
    "sebelum_pajak": {
      "s_d_bulan_lalu": 10000000.0,
      "periode_ini": 5000000.0,
      "s_d_sekarang": 15000000.0
    },
    "pph": {
      "s_d_bulan_lalu": 0.0,
      "periode_ini": 0.0,
      "s_d_sekarang": 0.0
    },
    "setelah_pajak": {
      "s_d_bulan_lalu": 10000000.0,
      "periode_ini": 5000000.0,
      "s_d_sekarang": 15000000.0
    }
  },
  "data": {
    "pendapatan": [
      {
        "kode_akun": "4.1",
        "nama_akun": "Pendapatan Operasional",
        "saldo_bln_lalu": 30000000.0,
        "saldo_periode_ini": 10000000.0,
        "saldo": 40000000.0,
        "rekening": [
          {
            "kode_akun": "4.1.01",
            "nama_akun": "Pendapatan Pinjaman",
            "saldo_bln_lalu": 25000000.0,
            "saldo_periode_ini": 8000000.0,
            "saldo": 33000000.0
          }
        ]
      }
    ],
    "beban": [],
    "pendapatan_non_ops": [],
    "beban_non_ops": []
  }
}
```

#### Cara render (mengikuti view tenant)

Empat section berurutan: `pendapatan` → `beban` → `pendapatan_non_ops` → `beban_non_ops`.

Tiap baris tampilkan **3 kolom** angka:

| Kolom             | Sumber              |
|-------------------|---------------------|
| s/d bulan lalu    | `saldo_bln_lalu`    |
| Periode ini       | `saldo_periode_ini` |
| s/d sekarang      | `saldo`             |

Hitungan ringkasan di `ringkasan.*` — render sebagai baris total sesuai struktur A/B/C:

| Baris                  | Sumber                                  |
|------------------------|-----------------------------------------|
| **A. Laba Rugi Operasional** | `ringkasan.lr_operasional.*` (3 kolom) |
| **B. Laba Rugi Non Operasional** | `ringkasan.lr_non_operasional.*` |
| **C. Sebelum Pajak**   | `ringkasan.sebelum_pajak.*`             |
| **PPh**                | `ringkasan.pph.*`                       |
| **C. Setelah Pajak**   | `ringkasan.setelah_pajak.*`             |

---

### 4.3 Arus Kas

```
GET /arus-kas?tahun=2025&bulan=6&hari=30
GET /arus-kas?tahun=2025&semester=1   # Semester I, tgl_kondisi=YYYY-06-30
GET /arus-kas?tahun=2025&semester=2   # Semester II, tgl_kondisi=YYYY-12-31
GET /arus-kas?tahun=2025              # Tahunan, tgl_kondisi=YYYY-12-31
```

#### Response

```json
{
  "success": true,
  "laporan": "Arus Kas",
  "kecamatan": "Kecamatan ABC",
  "periode": {
    "jenis": "Bulanan",
    "tgl_kondisi": "2025-06-30",
    "sub_judul": "Bulan Juni 2025"
  },
  "ringkasan": {
    "saldo_awal": 10000000.0,
    "total_masuk": 50000000.0,
    "total_keluar": 30000000.0,
    "kas_operasi": 15000000.0,
    "kas_investasi": 0.0,
    "kas_pendanaan": 0.0,
    "kenaikan_penurunan": 15000000.0,
    "saldo_akhir": 25000000.0,
    "group": [
      { "nama": "Arus Kas Masuk dari Aktivitas Operasi", "saldo": 50000000.0 },
      { "nama": "Arus Kas Keluar untuk Aktivitas Operasi", "saldo": 35000000.0 }
    ]
  },
  "data": [
    {
      "id": 1,
      "parent": "saldo_awal",
      "kategori": null,
      "nama": "Saldo Awal Bulan",
      "sub": 0,
      "saldo": 10000000.0,
      "detail": []
    },
    {
      "id": 2,
      "parent": "masuk",
      "kategori": "operasi",
      "nama": "Penerimaan Pinjaman",
      "sub": 0,
      "saldo": 50000000.0,
      "detail": [
        { "id": 10, "kode_akun": null, "nama_akun": "Pinjaman Kelompok A", "saldo": 30000000.0 },
        { "id": 11, "kode_akun": null, "nama_akun": "Pinjaman Kelompok B", "saldo": 20000000.0 }
      ]
    }
  ]
}
```

#### Cara render (mengikuti view tenant)

| Baris                              | Sumber                              |
|------------------------------------|-------------------------------------|
| Saldo Awal                         | `data[0]` (id=1, parent=saldo_awal) |
| Tiap parent                        | `data[]` lain → `nama` + `saldo`    |
| **Jumlah Aktivitas Operasi**       | `ringkasan.kas_operasi`             |
| **Jumlah Aktivitas Investasi**     | `ringkasan.kas_investasi`           |
| **Jumlah Aktivitas Pendanaan**     | `ringkasan.kas_pendanaan`           |
| **Kenaikan/Penurunan Kas**         | `ringkasan.kenaikan_penurunan`      |
| **Saldo Akhir Kas**                | `ringkasan.saldo_akhir`             |

Field `detail[]` di tiap parent berisi baris-baris child (sumber dana / penggunaan dana). Tampilkan sebagai sub-row indent.

---

### 4.4 Perubahan Ekuitas

```
GET /perubahan-ekuitas?tahun=2025&bulan=6&hari=30
```

#### Response

```json
{
  "success": true,
  "laporan": "Perubahan Ekuitas",
  "kecamatan": "Kecamatan ABC",
  "periode": {
    "tgl_kondisi": "2025-06-30",
    "sub_judul": "Bulan Juni 2025"
  },
  "ringkasan": {
    "ekuitas_awal": 100000000.0,
    "setoran": 5000000.0,
    "penarikan": -2000000.0,
    "dividen": 0.0,
    "koreksi": 0.0,
    "laba_rugi": 12000000.0,
    "ekuitas_akhir": 115000000.0
  },
  "data": [
    { "kode_akun": "3.1.01.01", "nama_akun": "Modal Disetor", "saldo_awal": 50000000.0, "saldo_akhir": 53000000.0, "mutasi": 3000000.0 },
    { "kode_akun": "3.1.01.02", "nama_akun": "Modal Belum Disetor", "saldo_awal": 10000000.0, "saldo_akhir": 12000000.0, "mutasi": 2000000.0 },
    { "kode_akun": "3.2.01.01", "nama_akun": "Tambahan Modal Disetor", "saldo_awal": 0.0, "saldo_akhir": 5000000.0, "mutasi": 5000000.0 },
    { "kode_akun": "3.2.01.02", "nama_akun": "Penarikan Modal", "saldo_awal": 0.0, "saldo_akhir": -2000000.0, "mutasi": -2000000.0 },
    { "kode_akun": "3.2.01.03", "nama_akun": "Dividen", "saldo_awal": 0.0, "saldo_akhir": 0.0, "mutasi": 0.0 },
    { "kode_akun": "3.2.02.01", "nama_akun": "Laba Rugi Tahun Berjalan", "saldo_awal": 40000000.0, "saldo_akhir": 52000000.0, "mutasi": 12000000.0 }
  ]
}
```

#### Cara render (mengikuti view tenant)

Tiap row di `data[]` punya 3 kolom angka: `saldo_awal`, `mutasi`, `saldo_akhir`.

| Baris                          | Sumber                              |
|--------------------------------|-------------------------------------|
| Tiap rekening ekuitas          | `data[]`                            |
| **Ekuitas Awal**               | `ringkasan.ekuitas_awal`            |
| **Setoran Modal**              | `ringkasan.setoran`                 |
| **Penarikan Modal**            | `ringkasan.penarikan`               |
| **Dividen**                    | `ringkasan.dividen`                 |
| **Koreksi**                    | `ringkasan.koreksi`                 |
| **Laba Rugi Tahun Berjalan**   | `ringkasan.laba_rugi`               |
| **Modal Akhir**                | `ringkasan.ekuitas_akhir`           |

> **Catatan:** `3.2.02.01` (Laba Rugi Tahun Berjalan) mengikuti special case (lihat §2). `saldo_akhir` = `laba_rugi(tgl_kondisi)`.

---

### 4.5 CALK (Bagian C)

```
GET /calk?tahun=2025&bulan=6&hari=30
```

> Endpoint ini khusus untuk **Bagian C** (rincian akun per rekening, mirip neraca). Untuk Bagian A/B (narasi, kebijakan, dll) tetap di-handle di sisi klien atau modul internal holding.

#### Response

```json
{
  "success": true,
  "laporan": "Catatan Atas Laporan Keuangan (CALK)",
  "kecamatan": "Kecamatan ABC",
  "periode": {
    "tgl_kondisi": "2025-06-30",
    "sub_judul": "Bulan Juni Tahun 2025",
    "tgl_mad": "2024-04-15"
  },
  "ringkasan": {
    "point_a": "Per 30 Juni 2025, kondisi keuangan Kecamatan ABC...",
    "total_aset": 150000000.0,
    "total_liabilitas_ekuitas": 150000000.0,
    "selisih": 0.0
  },
  "data": {
    "point_a": "Per 30 Juni 2025, kondisi keuangan Kecamatan ABC...",
    "catatan": "<narasi Bagian B dalam HTML/Markdown — null jika belum diisi>",
    "rincian_akun": [
      {
        "kode_akun": "1",
        "nama_akun": "Aset",
        "lev1": "1",
        "saldo": 150000000.0,
        "akun2": [
          {
            "kode_akun": "1.1",
            "nama_akun": "Aset Lancar",
            "saldo": 80000000.0,
            "akun3": [
              {
                "kode_akun": "1.1.01",
                "nama_akun": "Kas",
                "saldo": 50000000.0,
                "rekening": [
                  { "kode_akun": "1.1.01.01", "nama_akun": "Kas Besar", "saldo": 30000000.0 },
                  { "kode_akun": "1.1.01.02", "nama_akun": "Kas Kecil", "saldo": 20000000.0 }
                ]
              }
            ]
          }
        ]
      }
    ],
    "saldo_calk": [ /* collection Saldo::where('kode_akun', kd_kec)->where('tahun', tahun) */ ],
    "penandatangan": {
      "sekretaris": { "id": 1, "name": "...", "...": "..." } /* null jika belum ada */,
      "bendahara":  { "id": 2, "name": "...", "...": "..." },
      "pengawas":   null,
      "direktur":   { "id": 3, "name": "...", "...": "..." }
    }
  }
}
```

#### Cara render (mengikuti view tenant, line 230-306)

Tiap `rincian_akun[]` adalah **pohon 4-level**: `lev1 → akun2 → akun3 → rekening`.

Tampilan:

| Baris                         | Sumber                                | Style      |
|-------------------------------|---------------------------------------|------------|
| Header lev1                   | `rincian_akun[].nama_akun`            | Bold       |
| Subheader akun2               | `akun2[].nama_akun`                   | Bold       |
| Rincian akun3                 | `akun3[].saldo` (agregat)             | Indent 1   |
| Rincian per rekening          | `akun3[].rekening[].saldo`            | Indent 2   |
| **Jumlah {lev1 nama}**       | `rincian_akun[].saldo`                | Bold       |
| **Jumlah Liab + Ekuitas**     | `ringkasan.total_liabilitas_ekuitas`  | Bold       |

`point_a` adalah teks narasi Bagian A — tampilkan di section atas.

`penandatangan` — bisa `null` per role; tampilkan nama bila ada, lewati bila tidak.

---

## 5. Aturan Rendering

1. **Tidak ada kalkulasi klien.** Semua angka final (subtotal, total, selisih) sudah di JSON via `ringkasan`.
2. **Format angka:** `number_format($n, 2, ',', '.')` (desimal koma, ribuan titik) untuk tampilan Indonesia. JSON selalu pakai `.` untuk desimal.
3. **Tanda negatif:** tampilkan dengan prefix `-` (jangan kurung).
4. **Saldo 0:** tampilkan `0,00` atau strip, konsisten dengan view tenant.
5. **Hierarki indent:** padding-left 16/32/48 px per level.
6. **Row total/grand total:** bold + border-top 1px.
7. **Sub_judul:** tampilkan di header laporan, di bawah judul utama.
8. **Tgl kondisi:** format `d F Y` (contoh: `30 Juni 2025`).

---

## 6. Contoh Kode Klien (PHP)

```php
<?php
// holding-api-client.php

class HoldingLaporanClient
{
    private string $baseUrl;
    private string $apiToken;
    private string $tenantSlug;

    public function __construct(string $baseUrl, string $apiToken, string $tenantSlug)
    {
        $this->baseUrl    = rtrim($baseUrl, '/');
        $this->apiToken   = $apiToken;
        $this->tenantSlug = $tenantSlug;
    }

    private function request(string $endpoint, array $params): array
    {
        $url = $this->baseUrl . '/api/v1/holding/laporan/' . $endpoint
             . '?' . http_build_query($params);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'X-Holding-Token: ' . $this->apiToken,
                'X-Holding-Tenant: ' . $this->tenantSlug,
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT        => 30,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200) {
            throw new RuntimeException("HTTP {$code}: {$body}");
        }
        $json = json_decode($body, true);
        if (!($json['success'] ?? false)) {
            throw new RuntimeException("API error: " . ($json['message'] ?? 'unknown'));
        }
        return $json;
    }

    public function neraca(int $tahun, ?int $bulan = null, ?int $hari = null): array
    {
        return $this->request('neraca', array_filter([
            'tahun' => $tahun, 'bulan' => $bulan, 'hari' => $hari,
        ]));
    }

    public function labaRugi(int $tahun, ?int $bulan = null, ?int $hari = null): array
    {
        return $this->request('laba-rugi', array_filter([
            'tahun' => $tahun, 'bulan' => $bulan, 'hari' => $hari,
        ]));
    }

    public function arusKas(int $tahun, ?int $bulan = null, ?int $hari = null, ?int $semester = null): array
    {
        return $this->request('arus-kas', array_filter([
            'tahun' => $tahun, 'bulan' => $bulan, 'hari' => $hari, 'semester' => $semester,
        ]));
    }

    public function perubahanEkuitas(int $tahun, ?int $bulan = null, ?int $hari = null): array
    {
        return $this->request('perubahan-ekuitas', array_filter([
            'tahun' => $tahun, 'bulan' => $bulan, 'hari' => $hari,
        ]));
    }

    public function calk(int $tahun, ?int $bulan = null, ?int $hari = null): array
    {
        return $this->request('calk', array_filter([
            'tahun' => $tahun, 'bulan' => $bulan, 'hari' => $hari,
        ]));
    }
}

// --- Penggunaan ---
$client = new HoldingLaporanClient(
    'https://app.sidbm.net',
    'DbRz5uVJsttoNDuSYbYPHjDMpkPxoDOx1P75dl4G', // api_secret dari Master → License
    'app.sidbm.net'                                 // web_kec atau web_alternatif
);

$neraca = $client->neraca(2025, 6, 30);
echo "Kecamatan: {$neraca['kecamatan']}\n";
echo "Sub Judul: {$neraca['sub_judul']}\n";
echo "Total Aset: " . number_format($neraca['ringkasan']['total_aset'], 2) . "\n";
echo "Selisih: " . number_format($neraca['ringkasan']['selisih'], 2) . "\n";
```

---

## Lampiran: Ringkasan Field per Endpoint

| Endpoint                | Hirarki                                                | Field total di `ringkasan`                                       |
|-------------------------|--------------------------------------------------------|------------------------------------------------------------------|
| `neraca`                | lev1 → akun2 → akun3 (tanpa rekening)                  | `total_aset`, `total_liabilitas_ekuitas`, `selisih`              |
| `laba-rugi`             | 4 section × (group + rekening)                         | `pendapatan`, `beban`, `lr_operasional` (3kolom), `lr_non_operasional` (3kolom), `sebelum_pajak` (3kolom), `pph` (3kolom), `setelah_pajak` (3kolom) |
| `arus-kas`              | rows flat + `detail[]`                                 | `saldo_awal`, `total_masuk`, `total_keluar`, `kas_operasi`, `kas_investasi`, `kas_pendanaan`, `kenaikan_penurunan`, `saldo_akhir`, `group[]` |
| `perubahan-ekuitas`     | rows flat (rekening 3.x)                               | `ekuitas_awal`, `setoran`, `penarikan`, `dividen`, `koreksi`, `laba_rugi`, `ekuitas_akhir` |
| `calk` (Bagian C)       | lev1 → akun2 → akun3 → rekening                        | `point_a`, `total_aset`, `total_liabilitas_ekuitas`, `selisih`   |
