<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\KorisnickoImeRequest;
use App\Models\Korisnik;
use App\Services\BrisanjeNalogaService;
use App\Services\Uvoz\Rucni\CarobnjakUvoza;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NalogController extends Controller
{
    /** Ključ sesije: nalog je upravo napravljen Google prijavom, pa se nudi izbor korisničkog imena. */
    public const IZBOR_IMENA = 'izbor_korisnickog_imena';

    public function index(Request $request): View
    {
        return view('nalog.index', ['korisnik' => $request->user()]);
    }

    /**
     * Korak posle prve Google prijave: predloženo ime iz mejla se zadržava ili menja.
     */
    public function korisnickoIme(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has(self::IZBOR_IMENA)) {
            return redirect()->route('nalog');
        }

        return view('nalog.korisnicko-ime', ['korisnik' => $request->user()]);
    }

    public function sacuvajKorisnickoIme(KorisnickoImeRequest $request): RedirectResponse
    {
        /** @var Korisnik $korisnik */
        $korisnik = $request->user();
        $korisnik->update(['korisnicko_ime' => $request->validated('korisnicko_ime')]);

        if ($request->session()->pull(self::IZBOR_IMENA)) {
            return redirect()->intended(route('pocetna', absolute: false));
        }

        return redirect()->route('nalog')->with('status', 'Korisničko ime je promenjeno.');
    }

    public function izvoz(Request $request, BrisanjeNalogaService $servis): JsonResponse
    {
        /** @var Korisnik $korisnik */
        $korisnik = $request->user();

        return response()
            ->json($servis->izvoz($korisnik), options: JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ->header('Content-Disposition', 'attachment; filename="brokerporez-moji-podaci-'.now()->format('Y-m-d').'.json"');
    }

    public function destroy(Request $request, BrisanjeNalogaService $servis): RedirectResponse
    {
        /** @var Korisnik $korisnik */
        $korisnik = $request->user();

        // Upisivanje email adrese potvrđuje nameru i radi i za naloge bez lozinke (Google prijava).
        $request->merge(['potvrda_emaila' => mb_strtolower(trim((string) $request->input('potvrda_emaila')))]);
        $request->validate(['potvrda_emaila' => ['required', Rule::in([$korisnik->email])]]);

        CarobnjakUvoza::iz($request->session())?->zavrsi();
        $servis->obrisi($korisnik);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'Vaš nalog i svi podaci su trajno obrisani.');
    }
}
