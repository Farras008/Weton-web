<?php

final class Marriage23
{
    public const DATA = [
        1 => ["nama" => "Sri", "status" => "baik", "makna" => "Dalam tradisi Primbon Jawa, Sri dikaitkan dengan gambaran rumah tangga yang memiliki rezeki berlimpah dan kehidupan yang makmur. Keterangan ini dipahami sebagai bagian dari pembacaan budaya, bukan kepastian kondisi ekonomi pasangan."],
        2 => ["nama" => "Dana", "status" => "baik", "makna" => "Dana dalam perhitungan Primbon dikaitkan dengan kehidupan rumah tangga yang berkecukupan atau kaya harta. Makna ini dapat dibaca sebagai harapan akan kemampuan mengelola kebutuhan dan rezeki bersama."],
        3 => ["nama" => "Lara", "status" => "kurang baik", "makna" => "Lara dalam sumber Primbon dikaitkan dengan rintangan, cobaan, atau gangguan yang dapat dihadapi dalam kehidupan rumah tangga. Keterangan ini bukan prediksi kesehatan, melainkan bagian dari tradisi yang dapat direnungkan sebagai ajakan membangun ketahanan dan saling merawat."],
        4 => ["nama" => "Pati", "status" => "kurang baik", "makna" => "Pati dalam keterangan Primbon dikaitkan dengan kesedihan mendalam, perpisahan berat, atau kehilangan dalam perjalanan rumah tangga. Istilah ini merupakan bagian dari simbolisme tradisional dan tidak menyatakan kepastian tentang kematian atau masa depan seseorang."],
        5 => ["nama" => "Lunguh", "status" => "baik", "makna" => "Lunguh dikaitkan dalam tradisi Primbon dengan kehidupan rumah tangga yang tenteram, kokoh, selamat, serta memiliki derajat atau kedudukan yang baik. Makna tersebut dapat dibaca sebagai harapan akan kestabilan, ketenteraman, dan penghormatan dalam menjalani hidup bersama."],
    ];

    public static function calculate(int $total): array
    {
        $indeks = $total % 5;
        $indeks = $indeks === 0 ? 5 : $indeks;

        return ["indeks" => $indeks, "total" => $total, ...self::DATA[$indeks]];
    }
}
