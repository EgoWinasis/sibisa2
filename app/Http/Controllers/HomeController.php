<?php

namespace App\Http\Controllers;

use App\Models\ModelSettingDataSiswa;
use App\Models\ModelSettingGuru;
use App\Models\ModelSettingSiswa;
use App\Models\ModelTahunAjaran;
use App\Models\PelajarPancasila;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // Cek user aktif
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->isActive == 0) {
            Auth::logout();

            return redirect()
                ->route('login')
                ->with(
                    'error-active',
                    'Your account is inactive. Please contact Admin support.'
                );
        }

        // Ambil tahun ajaran aktif
        $tahunAjaran = ModelTahunAjaran::where('status', 1)->first();

        // Jika belum ada tahun ajaran aktif
        if (!$tahunAjaran) {
            return redirect()
                ->route('home')
                ->with(
                    'error',
                    'Belum ada tahun ajaran yang aktif. Silakan atur tahun ajaran terlebih dahulu.'
                );
        }

        $countAllSiswa = DB::table('students')->count();
        $nama = $user->name;
        $user_id = $user->id;

        /*
        |--------------------------------------------------------------------------
        | ADMIN
        |--------------------------------------------------------------------------
        */
        if ($user->role == 'admin') {

            // Siswa lulus
            $settingSiswa = ModelSettingSiswa::where('kelas', 7)->get();

            $countSiswaLulus = 0;

            foreach ($settingSiswa as $siswa) {
                $count = ModelSettingDataSiswa::where(
                    'id_setting_siswa',
                    $siswa->id
                )->count();

                $countSiswaLulus += $count;
            }

            // Jumlah guru/user yang belum dihapus
            $countGuru = DB::table('users')
                ->whereNull('deleted_at')
                ->count();

            return view('home')->with(compact(
                'countAllSiswa',
                'countSiswaLulus',
                'countGuru',
                'tahunAjaran',
                'nama'
            ));
        }

        /*
        |--------------------------------------------------------------------------
        | GURU
        |--------------------------------------------------------------------------
        */

        // Ambil setting guru tahun ajaran aktif
        $guru = ModelSettingGuru::where(
            'id_tahun_ajaran',
            $tahunAjaran->id
        )->get();

        $found_in_columns = null;
        $gurukelas = 0;

        $columns = [
            'id_guru1',
            'id_guru2',
            'id_guru3',
            'id_guru4',
            'id_guru5',
            'id_guru6',
        ];

        foreach ($guru as $row) {
            foreach ($columns as $column) {

                if ((int) $row->$column === (int) $user_id) {
                    $found_in_columns = $column;
                    break 2;
                }
            }
        }

        // Tentukan kelas guru
        switch ($found_in_columns) {
            case 'id_guru1':
                $gurukelas = 1;
                break;

            case 'id_guru2':
                $gurukelas = 2;
                break;

            case 'id_guru3':
                $gurukelas = 3;
                break;

            case 'id_guru4':
                $gurukelas = 4;
                break;

            case 'id_guru5':
                $gurukelas = 5;
                break;

            case 'id_guru6':
                $gurukelas = 6;
                break;

            default:
                $gurukelas = 0;
                break;
        }

        /*
        |--------------------------------------------------------------------------
        | Guru belum terdaftar sebagai wali kelas
        |--------------------------------------------------------------------------
        */
        if ($gurukelas == 0) {
            return view('home_guru')->with([
                'countSiswa' => 0,
                'countNilai' => 0,
                'tahunAjaran' => $tahunAjaran,
                'nama' => $nama,
                'gurukelas' => 0,
                'error' => 'Anda belum ditentukan sebagai guru kelas pada tahun ajaran aktif.'
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Setting siswa
        |--------------------------------------------------------------------------
        */
        $settingSiswa = ModelSettingSiswa::where(
            'id_tahun_ajaran',
            $tahunAjaran->id
        )
        ->where('kelas', $gurukelas)
        ->first();

        $countSiswa = 0;

        if ($settingSiswa) {
            $countSiswa = ModelSettingDataSiswa::where(
                'id_setting_siswa',
                $settingSiswa->id
            )->count();
        }

        /*
        |--------------------------------------------------------------------------
        | Jumlah nilai
        |--------------------------------------------------------------------------
        */
        $countNilai = PelajarPancasila::where(
            'tahun_ajaran',
            $tahunAjaran->tahun_ajaran
        )
        ->where('kelas', $gurukelas)
        ->count();

        return view('home_guru')->with(compact(
            'countSiswa',
            'countNilai',
            'tahunAjaran',
            'nama',
            'gurukelas'
        ));
    }
}
