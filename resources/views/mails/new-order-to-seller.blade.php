@extends('mails.layoutSeller')

@section('content')
<div class="header">
            Hi, {{ $order->user->name }},
        </div>
        <p>
            Terdapat order baru dari pelanggan dengan informasi sebagai berikut:
        </p>
        <table class="table">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Harga</th>
                    <th>Jumlah</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>{{ $item->product->name }}</td>
                        <td>Rp {{ number_format($item->product->price) }}</td>
                        <td>{{ $item->qty }}</td>
                        <td>Rp {{ number_format($item->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p><strong>Total: Rp {{ number_format($order->total) }}</strong></p>
        <p>
            Terimakasih telah bergabung dan mempercayakan jualan anda bersama dengan {{ config('app.name') }}.
        </p>
        <div class="footer">
            Terima kasih,<br>
            {{ config('app.name') }}
        </div>
@endsection
