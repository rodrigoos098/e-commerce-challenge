{{--
  Shopsugi — Email Base Layout Reference
  =======================================

  This file documents the email layout system for the Shopsugi e-commerce.
  The actual base layout lives in the published vendor mail views:

  - HTML layout:  resources/views/vendor/mail/html/layout.blade.php
  - HTML message:  resources/views/vendor/mail/html/message.blade.php
  - HTML header:   resources/views/vendor/mail/html/header.blade.php
  - HTML footer:   resources/views/vendor/mail/html/footer.blade.php
  - Theme CSS:     resources/views/vendor/mail/html/themes/default.css
  - Text layout:   resources/views/vendor/mail/text/layout.blade.php

  All email templates use the `<x-mail::message>` markdown component,
  which renders through the published vendor views above.

  Design tokens (from .impeccable.md):
  - kintsugi-500: #A67C1F (primary gold — CTAs, accents)
  - kintsugi-400: #C29A30 (gold hover — dividers, shimmer)
  - kintsugi-600: #8A6416 (gold dark — text on light)
  - warm-50:      #FDFAF5 (background)
  - warm-800:     #2D261D (heading text)
  - warm-500:     #8C7B66 (body secondary text)
  - cream:        #FFFDF7 (card backgrounds)
  - parchment:    #F5EFE0 (accent backgrounds)

  Fonts:
  - Body:    DM Sans (fallback: Segoe UI, Tahoma, sans-serif)
  - Display: Playfair Display (fallback: Georgia, Times New Roman, serif)

  Usage example:
  ──────────────
  <x-mail::message>
  # Heading (rendered in Playfair Display)

  Body text here (rendered in DM Sans).

  <x-mail::button :url="$url" color="primary">
  Call to Action
  </x-mail::button>

  <x-mail::panel>
  Info panel with gold accent border.
  </x-mail::panel>

  @include('emails.orders.partials.order-details', ['order' => $order])

  Com carinho,<br>
  **Equipe {{ config('app.name') }}**
  </x-mail::message>
--}}
