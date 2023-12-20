<!DOCTYPE html>
<html>

<head>
    <title>PRIMEDBA: {{ $podaci['title'] }}</title>
</head>

<body>
    <b>Naslov:</b><br/> {{ $podaci['title'] }}
    <br/><br/>
    <b>URI:</b> <br/> 
    <a href="{{ $podaci['uri'] }}">{{ $podaci['uri'] }}</a>
    <br/><br/>
    <b>Primedba: </b><br/> 
    {!! nl2br(e($podaci['note'])) !!}
    <br/><br/>
    Pozdrav,<br/>
    {{ $podaci['name'] }}
</body>

</html>
