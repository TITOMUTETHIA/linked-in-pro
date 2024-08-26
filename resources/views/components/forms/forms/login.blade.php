<x-layout>
    <x-page-heading>Log In</x-page-heading>
    <x-form.form method="POST" action="/LOGIN" enctype="multipart/form-data">
    
        <x-forms.input label="Your Name" name="name" /> 
        <x-forms.input label="Email" name="email" type="email" /> 
        <x-forms.input label="Password" name="password" type="password" /> 
        
        <x-form.button>Log In</x-forms.button>

    
    </x-form.form>

</x-layout>