<!-- resources/views/import_excel.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Excel</title>
</head>
<body>
    <h1>Import Excel File</h1>
    <form action="{{ route('import_excel_post4') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <label for="file">Choose Excel file:</label>
        <input type="file" name="file" id="file" required>
        <button type="submit">Upload</button>
    </form>
</body>
</html>
