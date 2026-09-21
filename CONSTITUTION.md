# Constitution — JadwalPintar

1. Security-first: semua user input divalidasi di trust boundary.
2. Test-first: failing test sebelum implementasi untuk logic non-trivial.
3. DB is source of truth — UI tidak boleh invent derived state.
4. No new dependency tanpa alasan eksplisit dan license check.
5. Scheduling engine = pure PHP, no external solver dependency.
6. Indonesian locale default, English technical terms OK.
7. Surgical changes only — setiap baris yang berubah harus traceable ke requirement.
8. Evidence before assertions — jangan claim "works" tanpa run command.
