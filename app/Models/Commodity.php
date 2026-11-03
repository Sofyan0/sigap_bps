<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commodity extends Model
{
    use HasFactory;

    protected $table = 'commodities';

    protected $fillable = [
        'tahun',
        'id_dok',
        'kuesioner',
        'prov',
        'kab',
        'kec',
        'nama_kec',
        'kode_kom',
        'nama_kom',
        'status_kom',
        'kode_kualitas',
        'nama_kualitas',
        'satuan',
        'status_kualitas',
        'januari_prev', 'januari',
        'februari_prev', 'februari',
        'maret_prev', 'maret',
        'april_prev', 'april',
        'mei_prev', 'mei',
        'juni_prev', 'juni',
        'juli_prev', 'juli',
        'agustus_prev', 'agustus',
        'september_prev', 'september',
        'oktober_prev', 'oktober',
        'november_prev', 'november',
        'desember_prev', 'desember'
    ];
}