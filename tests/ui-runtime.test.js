import { describe, expect, it } from 'vitest';
import {
  escapeAttribute,
  escapeHtml,
  formatDisplayDate,
  getOrderProgress,
} from '../resources/js/ui/runtime.js';

describe('ui runtime helpers', () => {
  it('escapes html-sensitive characters', () => {
    expect(escapeHtml(`"<script>alert('x')</script>"`)).toBe(
      '&quot;&lt;script&gt;alert(&#39;x&#39;)&lt;/script&gt;&quot;'
    );
  });

  it('normalizes unsafe attribute text', () => {
    expect(escapeAttribute('line one\n"quoted"')).toBe('line one &quot;quoted&quot;');
  });

  it('formats valid dates and preserves invalid input', () => {
    expect(formatDisplayDate('2026-04-06')).toMatch(/06|6/);
    expect(formatDisplayDate('not-a-date')).toBe('not-a-date');
    expect(formatDisplayDate(null)).toBe('-');
  });

  it('maps workflow progress for known and unknown states', () => {
    expect(getOrderProgress('waiting_review')).toEqual({
      value: 92,
      label: 'Menunggu review akhir',
      tone: 'accent',
    });

    expect(getOrderProgress('unknown_state')).toEqual({
      value: 12,
      label: 'Status belum dipetakan',
      tone: 'neutral',
    });
  });
});
