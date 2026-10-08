{{-- SW / EN links. The current language is shown but not linked. --}}
@props(['separator' => '·'])

<span {{ $attributes }}>
  @foreach (config('app.locales') as $code => $name)
    @if (!$loop->first) <span aria-hidden="true">{{ $separator }}</span> @endif
    @if ($code === app()->getLocale())
      <strong lang="{{ $code }}" aria-current="true">{{ $name }}</strong>
    @else
      <a href="{{ route('locale.switch', $code) }}" lang="{{ $code }}" hreflang="{{ $code }}" rel="nofollow">{{ $name }}</a>
    @endif
  @endforeach
</span>
