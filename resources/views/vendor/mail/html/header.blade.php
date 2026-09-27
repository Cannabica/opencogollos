@props(['url'])
@php
    // Marca blanca: el logo del header sale de platform.mail_header_logo.
    // Sin config no se muestra NINGÚN asset de marca (solo el nombre de la app).
    $logo = config('platform.mail_header_logo');
    $brandName = config('platform.brand_name') ?: config('app.name');
    $logoSrc = (filled($logo) && ! preg_match('#^https?://#i', $logo)) ? url($logo) : $logo;
@endphp
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;{{ filled($logoSrc) ? '' : ' color: #3d4852; font-size: 19px; font-weight: bold; text-decoration: none;' }}">
    @if (filled($logoSrc))
    <img src="{{ $logoSrc }}"
         alt="{{ $brandName }}"
         style="height: 40px; border: 0;"
         width="auto">
    @else
    {{ $brandName }}
    @endif
</a>
</td>
</tr>

