import DetailField from "./components/DetailField";
import FormField from "./components/FormField";
import IndexField from "./components/IndexField";

Nova.booting((app, store) => {
    app.component("index-image-gallery-field", IndexField);
    app.component("detail-image-gallery-field", DetailField);
    app.component("form-image-gallery-field", FormField);
});
