<x-layout>
    <x-page-heading>New Job</x-page-heading>
    <x-forms.form method="POST" action="/jobs">
        <x-forms.input label="Title" name="title" placeholder="CEO" /> 
        <x-forms.input label="Salary" name="salary" type="email" />
        <x-form.input label="Location" name="location" placeholder="Winter Park, Florida"  />


        <x-form.select label="Schedule" name="schedule">
            <option value="">Part Time</option>
            <option value="">Full Time</option>
        </x-form.select>
        <x-form.input label="Url" name="url" placeholder="https://acme.com/jobs/ceo-wanted" />
        <x-form.checkbox label="Feature (Cost Extra)" name="Featured" />
        <x-form.divider />
        <x-form.input label="Tags (comma seperated)" name="tags" placeholder="Laracast, video, education" />
        <x-form.button>Publish</x-form.button>
       

    </x-forms.form>
</x-layout>