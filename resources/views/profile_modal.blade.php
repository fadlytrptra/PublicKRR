<div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            @if(session('user'))
                <form action="{{ route('profile.update', session('user')->IdUser) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="modal-header">
                        <h5 class="modal-title">Edit Profile</h5>
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
                            <input type="text" name="NamaUser" class="form-control"
                                value="{{ $user->NamaUser }}" required>
                        </div>

                        <div class="mb-3">
                            <label>Nama Perusahaan</label>
                            <input type="text" name="NamaPerusahaan" class="form-control"
                                value="{{ $user->NamaPerusahaan }}">
                        </div>

                        <div class="mb-3">
                            <label>Alamat Perusahaan</label>
                            <textarea name="AlamatPerusahaan"
                                class="form-control">{{ $user->AlamatPerusahaan }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label>No HP</label>
                            <input type="text" name="NoHP" class="form-control"
                                value="{{ $user->NoHP }}">
                        </div>

                        <div class="mb-3">
                            <label>NPWP</label>
                            <input type="text" name="NPWP" class="form-control"
                                value="{{ $user->NPWP }}">
                        </div>

                        <div class="mb-3">
                            <label>Password (kosongkan jika tidak diubah)</label>
                            <div class="input-group">
                                <input type="password" name="Password" id="passwordField" class="form-control">

                                <button type="button" class="btn btn-outline-secondary" onclick="togglePassword()">
                                    <span id="eyeIcon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                            fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 9a3 3 0 1 0 0 6 3 3 0 1 0 0-6"></path>
                                            <path d="M12 19c7.63 0 9.93-6.62 9.95-6.68.07-.21.07-.43 0-.63-.02-.07-2.32-6.68-9.95-6.68s-9.93 6.61-9.95 6.67c-.07.21-.07.43 0 .63.02.07 2.32 6.68 9.95 6.68Zm0-12c5.35 0 7.42 3.85 7.93 5-.5 1.16-2.58 5-7.93 5s-7.42-3.84-7.93-5c.5-1.16 2.58-5 7.93-5"></path>
                                        </svg>
                                    </span>
                                </button>
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
    function togglePassword() {
        let input = document.getElementById('passwordField');
        if (input.type === "password") {
            input.type = "text";
        } else {
            input.type = "password";
        }
    }
</script>
