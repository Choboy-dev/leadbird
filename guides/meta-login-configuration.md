# Meta OAuth (Lead Bird app 1577553220617156)

## Instagram — Facebook Login (default)

```env
META_OAUTH_USE_LOGIN_CONFIG=1
META_OAUTH_SYSTEM_USER_CONFIG=0
```

Flow matches Hootsuite:

1. User clicks **Log in to Facebook** on the setup screen.
2. Meta OAuth at `facebook.com/.../dialog/oauth` (uses `META_FB_LOGIN_CONFIG_ID_INSTAGRAM` when login config is enabled).
3. User lands on **Which Instagram profiles do you want to add?** — Instagram Business accounts linked to Facebook Pages (`auth/meta/browse`).

Requirements:

- Instagram profile is **Business** (or Creator).
- That Instagram account is **linked to a Facebook Page**.
- User has **admin** on that Page.

Instagram permissions use the `instagram_*` family (for example `instagram_basic`, `instagram_manage_messages`, `instagram_manage_insights`, `instagram_content_publish`, `instagram_manage_contents`) — not the separate `instagram_business_*` Instagram Login product.

## Facebook / WhatsApp — Login Configuration (config_id)

```env
META_OAUTH_USE_LOGIN_CONFIG=1
META_OAUTH_SYSTEM_USER_CONFIG=0
```

OAuth uses **only** `config_id` (no bare `scope` in the URL). Meta docs: for Login for Business, `config_id` replaces `scope`.

### Create a Facebook Login Config (Dashboard)

Facebook Login for Business → Configurations → Create:

| Field | Value |
|-------|--------|
| Login variation | **General** |
| Access token | **User access token** (not System-user) |
| Assets | **Do not add Pages** — permissions only |
| Permissions | `pages_show_list`, `pages_read_engagement`, `pages_messaging`, `pages_manage_metadata` |

Copy the new Configuration ID → `META_FB_LOGIN_CONFIG_ID_FACEBOOK`.

For Instagram connect config (`META_FB_LOGIN_CONFIG_ID_INSTAGRAM`), include `instagram_basic`, `instagram_manage_messages`, `instagram_manage_insights`, `instagram_content_publish`, and `instagram_manage_contents` when publish/planner manage is enabled (`META_ENABLE_IG_PUBLISH=1`).

For Facebook Page connect, ensure `pages_manage_posts` is approved in App Review (requested via OAuth scopes when `META_ENABLE_PAGE_PUBLISH=1`) so users can edit and delete Page posts from the planner.

Configs that include **Page assets** always show Meta’s two-option Page screen. That is Meta’s UI, not Lead Bird.

## GDP flow

`META_OAUTH_USE_GDP_FLOW=0` — GDP returns “Something went wrong” for this app (not enabled by Meta).

## App Review

`instagram_*` and `pages_*` permissions may need App Review for production users outside Development mode testers.
