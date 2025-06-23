<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>メール認証</title>
</head>

<body>
    <h2>メール認証のお願い</h2>
    <p>以下のボタンをクリックして、メール認証を完了してください。</p>

    <a href="{{ $verificationUrl }}" style="display: inline-block; padding: 10px 20px; background-color: #FF5555; color: white; text-decoration: none; border-radius: 5px;">
        メール認証を完了する
    </a>

    <p>もし上記のボタンがクリックできない場合は、以下のURLをコピーしてブラウザに貼り付けてください：</p>
    <p>{{ $verificationUrl }}</p>

    <p>このメールに心当たりがない場合は、無視してください。</p>
</body>

</html>