<x-site-layout :gym="$gym" :site="$site">
    @include('site.sections.hero')
    @include('site.sections.about')
    @include('site.sections.forge')
    @include('site.sections.programs')
    @include('site.sections.training')
    @if ($trainers->isNotEmpty())
        @include('site.sections.trainers')
    @endif
    @if ($plans->isNotEmpty())
        @include('site.sections.pricing')
    @endif
    @include('site.sections.results')
    @include('site.sections.gallery')
    @include('site.sections.testimonials')
    @include('site.sections.contact')
    @include('site.sections.join')
</x-site-layout>
