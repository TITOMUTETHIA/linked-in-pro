@props(['job'])

<x-panel class="flex flex-col text-center">
            
    <div class="self-start text-sm">{{ $job->employer->name }}</div>
    <div class="py-8 font-bold">
        <h3 class="group-hover:text-blue-600 text-xl font-bold transition-colors duration-300">
            <a href="{{ $job->url}}" target="_blank"></a>
            {{$job->title}}</h3>

        <p>{{$job->salary}}</p>
    </div>

    <div>
        <div class="flex justify-between items-center mt-auto">
            <div>
                @foreach ($Job->tags as $tag)
                    <x-tag :$tag size="small" />
                @endforeach
                
                
            </div>

             <x-employer-logo :employer="$job->employer" :width="42" />
        </div> 

        

    
    </div>



</x-panel> 