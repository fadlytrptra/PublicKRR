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
                <p style="margin:0;">{{ $items->AlamatCustomer }}<br>
                                     {{ $items->KotaCustomer }}</p>
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
                        <td>Tanggal Kirim</td>
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
                    <tr>
                        <td>Tanggal Terima</td>
                        <td>: </td>
                        <td>
                            {{ \Carbon\Carbon::parse($items->TglAcc)->locale('id')->translatedFormat('d F Y, H:i:s') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    @php
        $satJual = strtoupper(trim($items->satJual ?? ''));
        $satPrimer = strtoupper(trim($items->satPrimer ?? ''));
        $satSekunder = strtoupper(trim($items->satSekunder ?? ''));
        $satTritier = strtoupper(trim($items->satTritier ?? ''));
        $satuanUmum = $satJual;
        $jumlahUmum = 0;

        if ($satJual == $satPrimer) {
            $jumlahUmum = $items->QtyPrimer;
        } elseif ($satJual == $satSekunder) {
            $jumlahUmum = $items->QtySekunder;
        } elseif ($satJual == $satTritier) {
            $jumlahUmum = $items->QtyTritier;
        }

        $jumlahUmum = number_format($jumlahUmum ?? 0, 0, ',', '.');
    @endphp

    <table style="border: 1px solid black;width: 100%;border-collapse: collapse;margin-top: 10px;">
        <tr>
            <th style="border: 1px solid black;padding:8px">Uraian</th>
            <th style="border: 1px solid black;padding:8px">Satuan</th>
            <th style="border: 1px solid black;padding:8px">Jumlah</th>
        </tr>
        <tr>
            <td style="border: 1px solid black;padding:8px">{{ $items->NamaKelompokUtama ?? '' }} <br> {{ $items->NamaType }}
                <br>
                {{ $items->No_PO }}
            </td>
            <td style="border: 1px solid black;padding:8px">{{ trim($satuanUmum) }} <br> {{ trim($items->satPrimer) }}
            </td>
            <td style="border: 1px solid black;padding:8px">{{ trim($jumlahUmum) }} <br>
                {{ number_format($items->QtyPrimer, 0, ',', '.') }}
            </td>
        </tr>
    </table>
    <div style="width: 98%;border: 1px solid black;margin-top: 10px;padding: 0.85%;">
        <h5>Alamat Kirim:</h5>
        <p>{{ $items->AlamatKirimCustomer ?? $items->AlamatKirimDO }}</p>
    </div>
    <div style="width: 98%;border: 1px solid black;margin-top: 10px;padding: 0.85%;">
        <h5>Keterangan:</h5>
        <p>{{ !empty(trim($items->Ket ?? '')) ? $items->Ket : '-' }}</p>
    </div>

    <table style="width:100%; margin-top:10px;" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width:55%; vertical-align:top; padding-left:10px;">

                <table
                    style="width:100%; border-bottom:1px solid black; border-collapse:collapse;">

                    {{-- Header --}}
                    <tr>
                        <td style="
                            width:30%;
                            text-align:center;
                            font-size:14px;
                            font-weight:bold;
                            vertical-align:top;
                            padding-top:28px;
                        ">
                            PENGIRIM
                        </td>

                        <td style="
                            width:70%;
                            text-align:center;
                            font-size:14px;
                            font-weight:bold;
                            padding-top:10px;
                        ">
                            TANDA TERIMA <br>
                            BARANG TERSEBUT TELAH KAMI TERIMA DALAM KEADAAN CUKUP DAN BAIK
                        </td>
                    </tr>

                    {{-- QR --}}
                    <tr>
                        <td style="height:120px; text-align:center; vertical-align:bottom; padding-top:15px;">
                            @if ($barcodeGudang)
                                <img src="{{ $ttCustomer }}" style="max-height:110px;">
                            @endif
                        </td>

                        <td style="height:120px; text-align:center; vertical-align:bottom; padding-top:15px;">
                            @if ($ttCustomer)
                                <img src="{{ $ttCustomer }}" style="max-height:110px;">
                            @endif
                        </td>
                    </tr>

                    {{-- Nama --}}
                    <tr>
                        <td style="text-align:center; font-size:15px; font-weight:normal; padding-bottom:15px;">
                            Ekspeditor
                        </td>

                        <td style="text-align:center; font-size:15px; font-weight:normal; padding-bottom:15px;">
                            {{ $namaCustomer ?? '-' }}
                        </td>
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
