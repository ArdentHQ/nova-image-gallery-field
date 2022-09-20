import DetailField from "./components/DetailField";
import FormField from "./components/FormField";

Nova.booting((app, store) => {
    app.component("detail-image-gallery-field", DetailField);
    app.component("form-image-gallery-field", FormField);
});
