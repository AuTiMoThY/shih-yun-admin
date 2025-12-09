export default defineAppConfig({
    ui: {
        colors: {
            primary: "blue",
            neutral: "slate"
        },
        button: {
            slots: {
                base: "cursor-pointer"
            }
        }
    },
    colorMode: {
        preference: "light" // 可選：'system' | 'light' | 'dark'
    }
});
