# Ⓐ Admin
Admin je routa, formulář a grid do administrace webů

![PHP](https://img.shields.io/packagist/dependency-v/liquiddesign/admin/php)
![Actions](https://github.com/liquiddesign/admin/actions/workflows/php.yml/badge.svg)
![Release](https://img.shields.io/github/v/tag/liquiddesign/admin?sort=semver)
![Prerelease](https://img.shields.io/github/v/tag/liquiddesign/admin?include_prereleases&label=pre&sort=semver)

## Dokumentace
☞ [Dropbox paper](https://paper.dropbox.com/doc/A-Admin--A~or44eHJ23vAYEiW14NKLG9Ag-Aw8oDJLgwscssr7oK3Nrd)

## Google OAuth přihlášení

Balíček obsahuje podporu pro přihlášení administrátorů přes Google OAuth 2.0. Závislost `league/oauth2-google` je součástí balíčku.

### Konfigurace

V NEON konfiguraci projektu:

```neon
admin:
    googleOAuth:
        enabled: true
        clientId: 'xxx.apps.googleusercontent.com'
        clientSecret: 'xxx'
        allowedDomains:
            - lqd.cz
        # autoCreateRole: 'admin'        # role pro automaticky vytvořené administrátory
        # sessionExpiration: '12 hours'   # výchozí platnost session
```

### Napojení v projektu

LoginPresenter buď extenduje `Abel\Admin\LoginPresenter` (který už trait používá):

```php
final class LoginPresenter extends \Abel\Admin\LoginPresenter
{
}
```

Nebo přidej `GoogleOAuthLoginTrait` do existujícího presenteru:

```php
use Admin\Controls\GoogleOAuthLoginTrait;

class LoginPresenter extends ... {
    use GoogleOAuthLoginTrait;

    public function actionDefault(): void
    {
        if ($this->processGoogleOAuth()) {
            return;
        }
        // ...
    }

    public function renderDefault(): void
    {
        $this->setupGoogleOAuthTemplate();
    }
}
```

V Latte šabloně `Login.default.latte` za `{/form}`:

```latte
{if $googleOAuthEnabled ?? false}
<div class="text-center mb-3 mt-3">
    <hr>
    <p>— nebo —</p>
    <a href="{plink default, googleLogin: 1}" class="btn btn-block btn-danger">
        <i class="fab fa-google mr-2"></i> Přihlásit přes Google
    </a>
</div>
{/if}
```

### Google Cloud Console

1. Vytvoř OAuth 2.0 credentials v [Google Cloud Console](https://console.cloud.google.com/apis/credentials)
2. Nastav Authorized redirect URI na URL akce `default` LoginPresenteru (např. `https://domena.cz/admin/login`)