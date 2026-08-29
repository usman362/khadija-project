{{-- A call-to-action button.

     Styles are inline, not classed: Outlook and a few mobile clients drop the
     <style> block in the layout head, and a button that arrives as bare blue
     text is the one nobody clicks. --}}
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin: 26px 0;">
    <tr>
        <td align="center" bgcolor="{{ $color ?? '#3b82f6' }}" style="border-radius: 10px;">
            <a href="{{ $url }}"
               style="display: inline-block; padding: 13px 28px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 15px; font-weight: 700; color: #ffffff; text-decoration: none; border-radius: 10px;">
                {{ $label }}
            </a>
        </td>
    </tr>
</table>

{{-- Every button is followed by its own URL as text. Some clients strip links
     entirely, and a mail whose only route forward is a dead button is a mail
     that failed. --}}
<p style="font-size: 12px; color: #64748b; word-break: break-all; margin: -14px 0 22px;">
    If the button doesn't work, copy this into your browser:<br>
    <span style="color: #475569;">{{ $url }}</span>
</p>
