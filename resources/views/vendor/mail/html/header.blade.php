@props(['url'])
<tr>
<td class="header" style="padding: 32px 0 24px 0; text-align: center; background-color: #FFFDF7;">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
@if (trim($slot) === 'Laravel')
<img src="https://laravel.com/img/notification-logo-v2.1.png" class="logo" alt="Laravel Logo">
@else
<span style="font-family: 'Playfair Display', Georgia, 'Times New Roman', serif; font-size: 26px; font-weight: 800; color: #2D261D; letter-spacing: 0.5px;">{!! $slot !!}</span>
@endif
</a>
<div class="header-divider" style="background-color: #C29A30; height: 2px; width: 48px; margin: 14px auto 0 auto; line-height: 2px; font-size: 2px;">&nbsp;</div>
</td>
</tr>
