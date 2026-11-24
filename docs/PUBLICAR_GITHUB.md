# Publicar la versión en GitHub

La rama local es `main`. La etiqueta `v0.1.0` identifica la primera entrega académica. El historial empieza con el respaldo inicial `3bcabf0` y el código consolidado se registra en `7e0599f`. Comprueba el commit completo de la etiqueta con `git rev-parse v0.1.0^{commit}` desde Bash, o `git rev-parse 'v0.1.0^{commit}'` desde PowerShell.

La carpeta local tiene los commits y la etiqueta. GitHub requiere una cuenta autenticada y un repositorio remoto. Se propone visibilidad privada para la entrega.

## Con GitHub CLI

Instala GitHub CLI si hace falta y entra con tu cuenta en el navegador. Cuando el nombre esté disponible, ejecuta estos comandos desde la raíz del panel. El último deja la release como borrador para que puedas revisar la publicación y sus accesos.

```powershell
gh auth login --web
gh repo create panel-mototaxi --private --source=. --remote=origin --push
git push origin v0.1.0
gh release create v0.1.0 --verify-tag --draft --title 'Aramovil v0.1.0' --notes-file docs/NOTAS_VERSION.md
```

[Creación de repositorios mediante GitHub CLI](https://cli.github.com/manual/gh_repo_create) · [Preparar una release](https://docs.github.com/en/repositories/releasing-projects-on-github/managing-releases-in-a-repository)

## Con el navegador

1. Inicia sesión en GitHub. Crea un repositorio **Private** desde [New repository](https://github.com/new). Deja sin marcar las opciones de plantilla, README y licencia para subir el historial local existente.
2. Copia la URL HTTPS del repositorio. En PowerShell, define `origin` con esa URL y sube la rama y la etiqueta.

```powershell
git remote add origin 'URL_HTTPS_DEL_REPOSITORIO'
git push -u origin main
git push origin v0.1.0
```

3. En **Releases**, elige la etiqueta `v0.1.0`, copia [las notas](NOTAS_VERSION.md) y guarda un borrador. Concede acceso sólo a quienes deben revisar la entrega.

Confirma con `git remote -v` y `git ls-remote --heads --tags origin`; abre el repositorio para verificar que `main` y `v0.1.0` son visibles para la cuenta autorizada. El repositorio contiene código, migraciones, pruebas y evidencia sintética. Antes de adjuntar material adicional, revisa que no incluya datos personales, claves o volcados.
