# Entity Registration Form

Provides custom registration forms that can be attached to existing entities and integrated with other modules, including Commerce.

## Features

- Any content entity (e.g. Nodes, Paragraphs, Commerce Products) can have an attached registration form.
- Unlimited, fieldable Registration Types.
- Easily register for one or more other users.
- (Optional) Multiple participants per registration. Unlimited, fieldable participant types.
- (Optional) Create new users for each participant.

## Usage

Visit the Admin pages and the Registrations section. Add or edit Registration and Participant types as needed.

To attach a registration form to any content entity (e.g. an Event content type or a commerce product type):

- Add an entity reference field.
- Select 'Reference' > 'Other...' as the field type.
- Select 'Configuration' > 'Registration type' as the type of item to reference.
- Under 'Manage display', select 'Registration Form' for the new field and click Save.

Now visit an entity with the field. If you added the field to an event content type, for example, visit an event node, edit it, and enter a registration type in your new field. Save and view the node, and you'll see a registration form included on the node.


## Roadmap

### Commerce Integration

- Add handler plugin system
- Add handler settings to registration types
- Handlers should declare which host entity types they work with
- The registration type selector on host entities should filter out types that don't support the host.
- Handler can inject form elements to the EntityRegistrationForm
- Handler can process those form element values upon submission
- Handler can declare formatter settings (e.g. variation view mode setting)
- Handlers can't be added to registration types that are attached to entities that wouldn't support them.
-
