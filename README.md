# StudyBuddy

Nowoczesna aplikacja webowa do tworzenia materiałów edukacyjnych i fiszek do nauki z wykorzystaniem sztucznej inteligencji.

## 📖 O aplikacji

**StudyBuddy** to inteligentna platforma edukacyjna, która wykorzystuje technologię OpenAI do automatycznego tworzenia materiałów do nauki. Aplikacja pozwala użytkownikom na:

-  **Organizację materiałów** - tworzenie folderów tematycznych i przesyłanie plików (PDF, DOC, TXT)
-  **AI-powered streszczenia** - automatyczne generowanie streszczeń z przesłanych dokumentów  
-  **Inteligentne fiszki** - tworzenie fiszek do nauki na podstawie materiałów za pomocą sztucznej inteligencji
-  **Intuicyjny interfejs** - responsywny design dostosowany do wszystkich urządzeń
-  **Bezpieczne konta** - system rejestracji i logowania użytkowników

Aplikacja została zbudowana z wykorzystaniem wzorców projektowych takich jak Repository Pattern, Service Layer i Dependency Injection.


## ⚙️ Instalacja

### 1. Klonowanie repozytorium
```bash
git clone <repository-url>
cd WDPAI-STUDYBUDY
```

### 2. Instalacja zależności
```bash
composer install
```

### 3. 🔑 Konfiguracja OpenAI API Key
**WAŻNE:** Aplikacja wymaga klucza API OpenAI do działania funkcji AI.

Edytuj plik `src/Services/OpenAIService.php` linię 13:
```php
$apiKey = "TWÓJ_OPENAI_API_KEY_TUTAJ";
```

**Jak uzyskać API Key:**
1. Idź na https://platform.openai.com/
2. Załóż konto lub zaloguj się
3. Przejdź do sekcji API Keys
4. Wygeneruj nowy klucz API
5. Wklej go w kodzie zamiast placeholder'a

### 4. Uruchomienie z Docker (Zalecane)
```bash
docker-compose up -d
```
Aplikacja będzie dostępna pod: http://localhost:8080

### 5. Ręczna instalacja
1. Skonfiguruj serwer web (Apache/Nginx) na katalog `public/`
2. Skonfiguruj bazę PostgreSQL
3. Uruchom skrypty z katalogu `docker/db/init.sql`



