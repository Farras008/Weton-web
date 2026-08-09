<?php

final class Marriage22
{
    public const DATA = [
        1 => ["nama" => "Wasesa Segara", "arti" => "Kekuasaan atau keluasan seperti samudra", "sumber" => "sudi memberi maaf, baik budi, (besar pribadinya), berwibawa, lapang dada.", "makna" => "Wasesa Segara mengingatkan pada keluasan samudra. Menurut tradisi Primbon Jawa, hasil ini menggambarkan hubungan yang diharapkan memiliki kelapangan hati, kesediaan saling memaafkan, dan wibawa dalam menghadapi keadaan."],
        2 => ["nama" => "Tunggak Semi", "arti" => "Tunggul pohon yang kembali bertunas", "sumber" => "Banyak Rejeki.", "makna" => "Tunggak Semi adalah gambaran sesuatu yang tampak telah berhenti, namun kembali tumbuh. Dalam pemaknaan Primbon Jawa, hasil ini dikaitkan dengan rezeki, harapan, dan peluang rumah tangga yang dapat berkembang kembali setelah masa sulit."],
        3 => ["nama" => "Satriya Wibawa", "arti" => "Ksatria yang berwibawa", "sumber" => "Kemuliaan serta keluhuran diperolehnya.", "makna" => "Satriya Wibawa mengacu pada sosok ksatria yang memiliki kewibawaan. Hasil ini dimaknai dalam tradisi Primbon sebagai hubungan yang berpotensi menjaga kehormatan, kemuliaan sikap, dan keteguhan dalam menjalankan tanggung jawab bersama."],
        4 => ["nama" => "Sumur Sinaba", "arti" => "Sumur yang didatangi atau tempat bertanya", "sumber" => "Sebagai sumber pengetahuan / tempat bertanya.", "makna" => "Sumur Sinaba melukiskan sumur yang menjadi tujuan orang untuk mengambil air atau mencari jawaban. Dalam pemaknaan Primbon, hubungan ini digambarkan sebagai ruang saling belajar, bertukar pertimbangan, dan menjadi tempat pulang ketika salah satu membutuhkan pemahaman."],
        5 => ["nama" => "Satriya Wirang", "arti" => "Ksatria yang menanggung malu", "sumber" => "Menanggung susah, (terkena musibah kematian) malu, penolaknya adalah darah: menyembelih hewan.", "makna" => "Satriya Wirang menggambarkan ksatria yang berhadapan dengan rasa malu atau ujian berat. Dalam sumber Primbon, hasil ini dikaitkan dengan kesusahan dalam kehidupan pasangan; keterangan tersebut adalah bagian dari tradisi budaya, bukan kepastian bahwa musibah akan terjadi."],
        6 => ["nama" => "Bumi Kapetak", "arti" => "Tanah yang dipetak atau terbelah", "sumber" => "Hatinya kalut, rajin bekerja, tahan menderita sengsara, selalu menjaga kebersihan, penolaknya adalah menanam tanah.", "makna" => "Bumi Kapetak memakai lambang tanah yang dipetak, yang dalam sumber membawa gambaran batin yang berat tetapi tetap bekerja keras. Hasil ini dapat dibaca sebagai ajakan untuk menguatkan kesabaran, merawat komunikasi, dan menjalani kesulitan bersama tanpa kehilangan kepedulian."],
        7 => ["nama" => "Lebu Katiup Angin", "arti" => "Debu yang tertiup angin", "sumber" => "Tidak terkabul keinginannya, sering berpindah rumah, sengsara, penolaknya menyebar tanah.", "makna" => "Lebu Katiup Angin menggambarkan debu yang mudah terbawa arah angin, sebagai lambang keadaan yang tidak selalu menetap. Dalam tradisi Primbon, hasil ini dikaitkan dengan keinginan yang kerap tertunda dan kesulitan rumah tangga; ia dapat menjadi bahan refleksi untuk membangun pijakan, kesepakatan, dan ketahanan bersama."],
    ];

    public static function calculate(int $total): array
    {
        $remainder10 = $total % 10;
        $hasil = $remainder10 <= 7 && $remainder10 !== 0 ? $remainder10 : null;

        if ($remainder10 > 7) {
            $hasil = $total % 7;
            $hasil = $hasil === 0 ? 7 : $hasil;
        }

        if ($hasil === null || !isset(self::DATA[$hasil])) {
            throw new LogicException("No. 22 tidak memiliki hasil untuk total neptu " . $total . ".");
        }

        return ["indeks" => $hasil, "total" => $total, ...self::DATA[$hasil]];
    }
}
