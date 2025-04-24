<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Fluffy Puffy Kids Zone</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-sky-100 flex items-center justify-center h-screen w-screen">
    <div class="max-w-sm w-full mx-auto flex flex-col items-center p-4">
        <!-- Logo -->
        <div class="mb-4">
            <img src="{{ asset('images/d4a70d2423d97599cccd8185d9046e60ff917ce0.png') }}" alt="Fluffy Puffy Kids Zone"
                class="w-64">
        </div>

        <!-- Login Form -->
        <div class="w-full">
            <h1 class="text-2xl font-semibold text-center mb-4">Login</h1>

            @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <ul class="list-disc ml-4">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form action="{{ route('login.submit') }}" method="POST">
                @csrf
                <!-- Username Input -->
                <div class="mb-3 relative">
                    <div class="relative">
                        <input type="email" id="email" name="email" placeholder="Username" value="{{ old('email') }}"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required autofocus>
                    </div>
                </div>

                <!-- Password Input -->
                <div class="mb-6 relative">
                    <input type="password" id="password" name="password" placeholder="Password"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                </div>

                <!-- Login Button -->
                <div class="mb-8">
                    <button type="submit"
                        class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2.5 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50"
                        style="background-color: #0080ff;">
                        Login
                    </button>
                </div>
            </form>
        </div>

        <!-- Powered By Footer -->
        <div class="text-center ">
            <p class="text-gray-600 text-sm mb-1">Powered By</p>
            <div>
                <img src="{{ asset('images/f9bbfcfd91e42e5bf5c8881f9a9ad448e4c06cf2.png') }}" alt="Devzur"
                    class="h-[281px] w-[471px]">
            </div>
        </div>
    </div>
</body>

</html>