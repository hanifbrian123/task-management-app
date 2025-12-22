<!DOCTYPE html>
<html>
<head>
    <title>Test Toggle</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>

<form method="POST" action="/tasks/1/toggle-status">
    @csrf
    @method('PATCH')
    <button type="submit">Toggle Task</button>
</form>

</body>
</html>
