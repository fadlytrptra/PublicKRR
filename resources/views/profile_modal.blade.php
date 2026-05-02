
<!--Hanya NPWP-->
@php
$user = session('user');
$idUser = optional($user)->IdUser;

$npwpCustomers = collect();

if ($idUser) {
    $npwpCustomers = DB::connection('ConnPublic')
        ->table('CustomerUserPublic')
        ->where('IdUser', $idUser)
        ->whereNotNull('NPWP')
        ->pluck('NPWP')
        ->unique()
        ->values();
}
@endphp

<!--Dengan ID Cust-->
{{-- @php
$npwpCustomers = DB::connection('ConnPublic')
    ->table('CustomerUserPublic')
    ->where('IdUser', session('user')->IdUser)
    ->whereNotNull('NPWP')
    ->select('IDCust', 'NPWP')
    ->get();
@endphp --}}



<div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            @if(session('user'))
                <form action="{{ route('profile.update', session('user')->IdUser) }}" method="POST" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <h5 class="modal-title">Informasi User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        @php $user = session('user'); @endphp

                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" class="form-control bg-secondary-subtle" value="{{ $user->Email }}" readonly>
                        </div>

                        <div class="mb-3">
                            <label>Nama</label>
                            <input type="text" name="NamaUser" class="form-control bg-secondary-subtle"
                                value="{{ old('NamaUser', $user->NamaUser) }}" readonly>
                            <div class="invalid-feedback">Nama wajib diisi</div>
                        </div>

                        <div class="mb-3">
                            <label>Nama Perusahaan</label>
                            <input type="text" name="NamaPerusahaan" class="form-control bg-secondary-subtle"
                                value="{{ $user->NamaPerusahaan }}" readonly>
                        </div>

                        <div class="mb-3">
                            <label>Alamat Perusahaan</label>
                            <textarea name="AlamatPerusahaan"
                                class="form-control bg-secondary-subtle" readonly>{{ $user->AlamatPerusahaan }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label>No HP</label>
                            <input type="text" name="NoHP" class="form-control bg-secondary-subtle"
                                value="{{ old('NoHP', $user->NoHP) }}" readonly>
                            <div class="invalid-feedback">No HP wajib diisi</div>
                        </div>

                        <div class="mb-3">
                            <label>NPWP User</label>
                            <input type="text" name="NPWP" class="form-control bg-secondary-subtle"
                                value="{{ old('NPWP', $user->NPWP) }}" readonly>
                            <div class="invalid-feedback">NPWP wajib diisi</div>
                        </div>

                       {{-- <div class="mb-3">
                            <label>NPWP Perusahaan yang terhubung dengan User</label>

                           @if($npwpCustomers->count())
                                <div class="form-control bg-secondary-subtle">
                                    {{ $npwpCustomers->implode(', ') }}
                                </div>
                            @else
                                <div class="form-control bg-secondary-subtle">
                                    Tidak ada NPWP customer
                                </div>
                            @endif
                        </div> --}}

                        <div class="mb-3">
                            <label>Password (kosongkan jika tidak diubah)</label>
                            <div class="input-group">
                                <input type="password" name="Password" id="passwordField"
                                    class="form-control @error('Password') is-invalid @enderror">

                               <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">
                                    <span id="eyeIcon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 9a3 3 0 1 0 0 6 3 3 0 1 0 0-6"></path>
                                            <path d="M12 19c7.63 0 9.93-6.62 9.95-6.68.07-.21.07-.43 0-.63-.02-.07-2.32-6.68-9.95-6.68s-9.93 6.61-9.95 6.67c-.07.21-.07.43 0 .63.02.07 2.32 6.68 9.95 6.68Zm0-12c5.35 0 7.42 3.85 7.93 5-.5 1.16-2.58 5-7.93 5s-7.42-3.84-7.93-5c.5-1.16 2.58-5 7.93-5"></path>
                                        </svg>
                                    </span>
                                </button>
                                 @error('Password')
                                    <div class="invalid-feedback d-block">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Simpan</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    </div>
                </form>
            @endif

        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

    let modalEl = document.getElementById('profileModal');
    let form = modalEl?.querySelector('form');

    if (!modalEl || !form) return;

    let nama = form.querySelector('[name="NamaUser"]');
    let nohp = form.querySelector('[name="NoHP"]');
    let npwp = form.querySelector('[name="NPWP"]');
    let inputs = form.querySelectorAll('input');

    form.addEventListener('submit', function (e) {
        let isValid = true;

        [nama, nohp, npwp].forEach(input => {
            if (input.value.trim() === '') {
                input.classList.add('is-invalid');
                isValid = false;
            } else {
                input.classList.remove('is-invalid');
            }
        });

        if (!isValid) {
            e.preventDefault();
        }
    });

    inputs.forEach(input => {
        input.addEventListener('input', function () {
            if (this.value.trim() !== '') {
                this.classList.remove('is-invalid');
            }
        });
    });

    window.togglePassword = function () {
        let input = document.getElementById('passwordField');
        if (input) {
            input.type = input.type === "password" ? "text" : "password";
        }
    };

    modalEl.addEventListener('hidden.bs.modal', function () {
        location.reload();
    });

    @if ($errors->any())
        let modal = new bootstrap.Modal(modalEl);
        modal.show();
    @endif

});
</script>
