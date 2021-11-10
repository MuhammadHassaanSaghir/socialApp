<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    
    @component('mail::message')
    # Order Shippedxchcjh

    Your order has been shipped!
    @component('mail::button', ['url' => $url])
    click me
    @endcomponent    
    <a href="{{$url}}">{{$url}}</a>


    Thanks
    @endcomponent


</body>
</html>