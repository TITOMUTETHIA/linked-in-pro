<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Pixel position</title>
    @vite(['resources/js/app.js'])
</head>
<body>
    <div>
        <nav>
            <div>
                <a href="">
                    <img src="@vite('resources/images/logo.jpeg')" alt="">
                </a>
            </div>
            <div>
                <a href="#">Jobs</a>
                <a href="#">Career</a>
                <a href="#">Salaries</a>
                <a href="#">Companies</a>
            </div>
            <div>
                <a href="#">Post a job</a>
            </div>

        </nav>
        <main>
            {{ $slot }}
        </main>
    </div>
</body>
</html>