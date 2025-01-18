@extends('layouts.auth')
@section('content')
    <form method = "POST" action = "{{ route('register') }}">
        @csrf
        <div>
            <label class = "block font-medium text-sm text-gray-700" for = "name">Name</label>
            <input class = "rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 block mt-1 w-full" id = "name" type = "text" name = "name" required = "required" autofocus = "autofocus" />
        </div>
        <div class = "mt-4">
            <label class = "block font-medium text-sm text-gray-700" for = "email">Email</label>
            <input class = "rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 block mt-1 w-full" id = "email" type = "email" name = "email" required = "required" />
        </div>
        <div class = "mt-4">
            <label class = "block font-medium text-sm text-gray-700" for = "password">Password</label>
            <input class = "rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 block mt-1 w-full" id = "password" type = "password" name = "password" required = "required" autocomplete = "new-password" />
        </div>
        <div class = "mt-4">
            <label class = "block font-medium text-sm text-gray-700" for = "password_confirmation">Confirm Password</label>
            <input class = "rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 block mt-1 w-full" id = "password_confirmation" type = "password" name = "password_confirmation" required = "required" />
        </div>
        <div class = "flex items-center justify-end mt-4">
            <button type = "submit" class = "inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150 ml-4">Register</button>
        </div>
    </form>
@endsection