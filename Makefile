build: tailwind hugo 
tailwind:
	npx tailwindcss -i ./themes/dantleech/css/main.css -o ./themes/dantleech/static/css/style.css
hugo:
	hugo
