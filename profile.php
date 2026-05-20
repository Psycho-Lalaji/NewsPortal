<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile | News Portal</title>
    <link rel="stylesheet" href="profile.css">
</head>
<body>

<div class="profile-wrapper">
    <div class="profile-card">

        <div class="profile-header">
            <div class="avatar-box" id="avatarBox">
                <span class="avatar-letter" id="avatarLetter">S</span>
                <img id="avatarImg" src="" alt="">
                <div class="avatar-hover">📷 Change</div>
            </div>
            <input type="file" id="avatarInput" accept="image/*">
            <div>
                <p class="label-small">NEWS PORTAL</p>
                <h1>User Profile</h1>
                <p class="subtext">Manage your personal information.</p>
            </div>
        </div>

        <form id="profileForm">

            <div class="form-row">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" value="Suwarna Bista">
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" value="suwarna_bista">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" value="suwarna@gmail.com">
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" value="+977 9800000000">
                </div>
            </div>

            <div class="form-group">
                <label>Bio</label>
                <textarea name="bio" rows="4">Frontend developer working on the News Portal project.</textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">Update Profile</button>
                <a href="saved_news.php" class="btn-secondary">Saved News</a>
            </div>

            <p id="statusMsg"></p>
        </form>

    </div>
</div>

<script>
    // Avatar preview
    var box = document.getElementById('avatarBox');
    var inp = document.getElementById('avatarInput');
    var img = document.getElementById('avatarImg');
    var ltr = document.getElementById('avatarLetter');
    var nameField = document.querySelector('[name="full_name"]');

    box.onclick = function(){ inp.click(); };
    inp.onchange = function(){
        var f = this.files[0];
        if(!f) return;
        var r = new FileReader();
        r.onload = function(e){ img.src = e.target.result; img.style.display='block'; ltr.style.display='none'; };
        r.readAsDataURL(f);
    };
    nameField.oninput = function(){
        ltr.textContent = (this.value[0] || '?').toUpperCase();
    };

    // Form submit
    document.getElementById('profileForm').onsubmit = function(e){
        e.preventDefault();
        var s = document.getElementById('statusMsg');
        s.textContent = 'Profile updated successfully!';
        s.className = 'status-success';
        setTimeout(function(){ s.textContent=''; }, 4000);
    };
</script>

</body>
</html>