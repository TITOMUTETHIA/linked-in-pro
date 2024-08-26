<x-layout>
    <x-page-heading>Register</x-page-heading>
    <x-form.form method="POST" action="/register" enctype="multipart/form-data">
    
        <x-forms.input label="Your Name" name="name" /> 
        <x-forms.input label="Email" name="email" type="email" /> 
        <x-forms.input label="Password" name="password" type="password" /> 
        <x-forms.input label="Password Confirmation" name="passwordconfirmation" type="password" />
        
        <x-form.divider />

        

        <x-forms.input label="Employer Name" name="employer" />
        <x-forms.input label="Employer Logo" name="logo" type="file" />

        <x-form.button>Create Account</x-forms.button>

    
    </x-form.form>

</x-layout>