<style>
    h2,
    h3,
    h4 {
        margin: 0;
        padding: 0;
        text-align: center;
    }

    h5,
    p {
        margin: 0;
        padding: 0;
    }

    .signature_imgCanvas {
        max-height: 80px;
    }
</style>
<div style="width: 16cm;height: 20.5cm;border: 1px solid black;padding: 10px;box-sizing: border-box;"
    contenteditable="true">
    <h2>PT. KERTA RAJASA RAYA</h2>
    <h4>JL RAYA TROPODO No. 1 WARU - SIDOARJO - INDONESIA</h4>
    <h4>TELP (031) 8669595 (HUNTING)</h4>
    <h3>SURAT PENGANTAR PENGIRIMAN BARANG</h3>
    <table style="width:100%; margin-top:10px;" cellpadding="0" cellspacing="0">
        <tr>
            <!-- LEFT SIDE -->
            <td style="width:50%; vertical-align:top; border:1px solid black; padding:8px;">
                <h5 style="margin:0;">Kepada Yth.</h5>
                <h5 style="margin:0;">{{ $items->NamaCust }}</h5>
                <p style="margin:0;">{{ $items->Alamat }}</p>
            </td>

            <!-- RIGHT SIDE -->
            <td style="width:50%; vertical-align:top; padding-left:15px;">
                <table>
                    <tr>
                        <td>No. SJ</td>
                        <td>: </td>
                        <td>{{ $items->IDPengiriman }}</td>
                    </tr>
                    <tr>
                        <td>Tanggal</td>
                        <td>: </td>
                        <td>
                            {{ \Carbon\Carbon::parse($items->TglKirim)->locale('id')->translatedFormat('d-F-Y') }}
                        </td>
                    </tr>
                    <tr>
                        <td>Truk No.</td>
                        <td>: </td>
                        <td>{{ $items->TrukNopol }}</td>
                    </tr>
                    <tr>
                        <td>No. SP</td>
                        <td>: </td>
                        <td>{{ $items->SuratPesanan }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    @php
        $satuanUmum = '';
        $jumlahUmum = '';
        if ($items->Satuan == trim($items->satSekunder)) {
            $satuanUmum = trim($items->satSekunder);
            $jumlahUmum = number_format($items->QtySekunder, 0, ',', '.');
        } elseif ($items->Satuan == trim($items->SatTRitier)) {
            $satuanUmum = trim($items->SatTRitier);
            $jumlahUmum = number_format($items->QtyTritier, 0, ',', '.');
        }
    @endphp
    <table style="border: 1px solid black;width: 100%;border-collapse: collapse;margin-top: 10px;">
        <tr>
            <th style="border: 1px solid black;padding:8px">Uraian</th>
            <th style="border: 1px solid black;padding:8px">Satuan</th>
            <th style="border: 1px solid black;padding:8px">Jumlah</th>
        </tr>
        <tr>
            <td style="border: 1px solid black;padding:8px">{{ $items->NAMATYPEBARANG }} <br> {{ $items->NamaType }}
                <br>
                {{ $items->NO_PO }}
            </td>
            <td style="border: 1px solid black;padding:8px">{{ trim($satuanUmum) }} <br> {{ trim($items->satPrimer) }}
            </td>
            <td style="border: 1px solid black;padding:8px">{{ trim($jumlahUmum) }} <br>
                {{ number_format($items->QtyPrimer, 0, ',', '.') }}
            </td>
        </tr>
    </table>
    <div style="width: 98%;border: 1px solid black;margin-top: 10px;padding: 0.85%;">
        <h5>Syarat Penyerahan:</h5>
        <p>Dikirim ke: {{ $items->AlamatKirim }}</p>
    </div>
    <table style="width:100%; margin-top:10px;" cellpadding="0" cellspacing="0">
        <tr>

            <!-- LEFT COLUMN -->
            <td style="width:45%; vertical-align:top; padding-right:10px;">

                <!-- SIGNATURE TABLE -->
                <table style="width:100%; text-align:center; border-bottom:1px solid black; border-collapse:collapse;">

                    <tr>
                        <td style="font-weight:bold;">
                            PENGIRIM,
                        </td>
                    </tr>

                    <!-- Signature Area -->
                    <tr>
                        <td style="height:90px; vertical-align:bottom;">
                            @if (!empty($ttdBase64_1))
                                <img src="{{ $ttdBase64_1 }}" style="display:block; margin:0 auto; max-height:70px;">
                            @endif
                        </td>
                    </tr>

                    <!-- Name -->
                    <tr>
                        <td>
                            {{ $items->NamaMng ?? '' }}
                        </td>
                    </tr>

                </table>

                <!-- COPY INFO TABLE -->
                <table style="width:100%; font-size:12px; margin-top:10px;">
                    <tr>
                        <td>Lembar ke 1,2,3</td>
                        <td>=</td>
                        <td>Untuk Pembeli</td>
                    </tr>
                    <tr>
                        <td>Lembar ke 4</td>
                        <td>=</td>
                        <td>Untuk Bagian Piutang</td>
                    </tr>
                    <tr>
                        <td>Lembar ke 5</td>
                        <td>=</td>
                        <td>Untuk Gudang</td>
                    </tr>
                    <tr>
                        <td>Lembar ke 6</td>
                        <td>=</td>
                        <td>Untuk Adm. Kantor</td>
                    </tr>
                    <tr>
                        <td>Lembar ke 7</td>
                        <td>=</td>
                        <td>Untuk Satpam</td>
                    </tr>
                </table>

            </td>

            <!-- RIGHT COLUMN -->
            <td style="width:55%; vertical-align:top; padding-left:10px;">

                <table
                    style="width:100%; text-align:center; border-bottom:1px solid black; border-collapse:collapse; font-size:14px; font-weight:bold;">

                    <tr>
                        <td>
                            TANDA TERIMA <br>
                            BARANG TERSEBUT TELAH KAMI TERIMA DALAM KEADAAN CUKUP DAN BAIK
                        </td>
                    </tr>

                    <tr>
                        <td style="height:110px;"></td>
                    </tr>

                </table>

                <table style="width:100%; font-size:12px; margin-top:10px;">
                    <tr>
                        <td><strong>Note :</strong></td>
                    </tr>
                    <tr>
                        <td>
                            Apabila barang belum terbayar maka barang yang terkirim merupakan barang titipan
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
