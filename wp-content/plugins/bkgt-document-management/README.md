# BKGT Document Management System

Ett komplett dokumenthanteringssystem för BKGT Ledare WordPress-webbplatsen, designat för att hantera dokument med versionskontroll, åtkomstkontroll och säker filhantering.

## Funktioner

### 📁 Dokumenthantering
- **Säker filuppladdning** med validering av filtyper och storlek
- **Versionskontroll** - spåra alla ändringar och återställ tidigare versioner
- **Kategorisering** - organisera dokument i hierarkiska kategorier
- **Sökfunktionalitet** - hitta dokument snabbt med textsökning

### 🔒 Åtkomstkontroll
- **Rollbaserad åtkomst** - olika behörighetsnivåer (visa, redigera, hantera)
- **Användarspecifik åtkomst** - ge specifika användare åtkomst
- **Lagspecifik åtkomst** - begränsa åtkomst till specifika lag
- **Team-integration** - fungerar med användarhanteringssystemet

### 📊 Rapportering och Statistik
- **Nedladdningsloggning** - spåra alla nedladdningar
- **Användningsstatistik** - se populära dokument och användningsmönster
- **Versionshistorik** - full historik över alla ändringar

### 🎨 Användargränssnitt
- **Svenskt gränssnitt** - helt på svenska för enkel användning
- **Responsiv design** - fungerar på alla enheter
- **Drag-and-drop** - enkel filuppladdning
- **Grid/List-vy** - flexibel visning av dokument

## Installation

1. Ladda upp plugin-mappen till `/wp-content/plugins/`
2. Aktivera pluginet i WordPress admin
3. Pluginet skapar automatiskt nödvändiga databastabeller
4. Konfigurera inställningar under "Dokument > Inställningar"

## Användning

### För Administratörer

1. **Skapa dokumentkategorier** under "Dokument > Kategorier"
2. **Ladda upp dokument** via "Dokument > Lägg till nytt"
3. **Ställ in åtkomstregler** för varje dokument
4. **Övervaka användning** via dashboard-statistik

### För Användare

1. **Bläddra dokument** via "Dokument"-menyn
2. **Filtrera efter kategori** i sidofältet
3. **Sök efter dokument** med sökfältet
4. **Ladda ner dokument** med ett klick

## Tekniska Detaljer

### Filtyper som stöds
- PDF (.pdf)
- Microsoft Word (.doc, .docx)
- Microsoft Excel (.xls, .xlsx)
- Microsoft PowerPoint (.ppt, .pptx)
- Textfiler (.txt, .rtf)
- Bilder (.jpg, .jpeg, .png, .gif)

### Databasstruktur
- `wp_bkgt_document_versions` - dokumentversioner
- `wp_bkgt_document_access` - åtkomstregler
- `wp_bkgt_document_downloads` - nedladdningslogg

### Säkerhet
- Filerna lagras utanför webroot med .htaccess-skydd
- Alla nedladdningar loggas
- Åtkomstkontroll på både användar- och filnivå
- CSRF-skydd på alla AJAX-anrop

## Integration

Pluginet integrerar sömlöst med:
- **BKGT User Management** - för team- och rollhantering
- **BKGT Inventory System** - konsekvent användargränssnitt
- **WordPress User System** - utnyttjar inbyggda användarroller

## Utvecklingsinformation

### Klassstruktur
- `BKGT_Document` - huvudsaklig dokumenthantering
- `BKGT_Document_Version` - versionshantering
- `BKGT_Document_Access` - åtkomstkontroll
- `BKGT_Document_Category` - kategorihantering
- `BKGT_Document_Admin` - admin-gränssnitt

### Hooks och Filter
- `bkgt_document_upload` - körs vid filuppladdning
- `bkgt_document_access_check` - filter för åtkomstkontroll
- `bkgt_document_download` - körs vid nedladdning

## Felsökning

### Vanliga Problem

**Filer laddas inte upp**
- Kontrollera filstorleksgräns i PHP-inställningar
- Verifiera att uppladdningsmappen är skrivbar

**Åtkomst nekad**
- Kontrollera användarroller och teamtillhörighet
- Verifiera att åtkomstregler är korrekt inställda

**Sök fungerar inte**
- Kontrollera att databastabeller är korrekt skapade
- Återindexera dokument om nödvändigt

## Changelog

### Version 1.0.0
- Initial release
- Grundläggande dokumenthantering
- Versionskontroll
- Åtkomstkontroll
- Svenskt gränssnitt

## Licens

Detta plugin är licensierat under GPL v2 eller senare.

## Support

För support och frågor, kontakta utvecklaren eller skapa ett issue i projektets repository.