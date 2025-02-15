***

# uri





* Full name: `\yxorP\lib\proxy\psr7\uri`
* This class implements:
[`\yxorP\lib\psr\http\message\uriInterface`](../../psr/http/message/uriInterface.md)


## Constants

| Constant | Visibility | Type | Value |
|:---------|:-----------|:-----|:------|
|`HTTP_DEFAULT_HOST`|public| |&#039;localhost&#039;|

## Properties


### defaultPorts



```php
private static $defaultPorts
```



* This property is **static**.


***

### charUnreserved



```php
private static $charUnreserved
```



* This property is **static**.


***

### charSubDelims



```php
private static $charSubDelims
```



* This property is **static**.


***

### replaceQuery



```php
private static $replaceQuery
```



* This property is **static**.


***

### scheme



```php
private $scheme
```






***

### userInfo



```php
private $userInfo
```






***

### host



```php
private $host
```






***

### port



```php
private $port
```






***

### path



```php
private $path
```






***

### query



```php
private $query
```






***

### fragment



```php
private $fragment
```






***

## Methods


### __construct



```php
public __construct(mixed $uri = &#039;&#039;): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uri` | **mixed** |  |




***

### isDefaultPort



```php
public static isDefaultPort(\yxorP\lib\psr\http\message\uriInterface $uri): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uri` | **\yxorP\lib\psr\http\message\uriInterface** |  |




***

### isAbsolute



```php
public static isAbsolute(\yxorP\lib\psr\http\message\uriInterface $uri): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uri` | **\yxorP\lib\psr\http\message\uriInterface** |  |




***

### isNetworkPathReference



```php
public static isNetworkPathReference(\yxorP\lib\psr\http\message\uriInterface $uri): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uri` | **\yxorP\lib\psr\http\message\uriInterface** |  |




***

### isAbsolutePathReference



```php
public static isAbsolutePathReference(\yxorP\lib\psr\http\message\uriInterface $uri): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uri` | **\yxorP\lib\psr\http\message\uriInterface** |  |




***

### isRelativePathReference



```php
public static isRelativePathReference(\yxorP\lib\psr\http\message\uriInterface $uri): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uri` | **\yxorP\lib\psr\http\message\uriInterface** |  |




***

### isSameDocumentReference



```php
public static isSameDocumentReference(\yxorP\lib\psr\http\message\uriInterface $uri, \yxorP\lib\psr\http\message\uriInterface $base = null): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uri` | **\yxorP\lib\psr\http\message\uriInterface** |  |
| `$base` | **\yxorP\lib\psr\http\message\uriInterface** |  |




***

### removeDotSegments



```php
public static removeDotSegments(mixed $path): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$path` | **mixed** |  |




***

### resolve



```php
public static resolve(\yxorP\lib\psr\http\message\uriInterface $base, mixed $rel): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$base` | **\yxorP\lib\psr\http\message\uriInterface** |  |
| `$rel` | **mixed** |  |




***

### withoutQueryValue



```php
public static withoutQueryValue(\yxorP\lib\psr\http\message\uriInterface $uri, mixed $key): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uri` | **\yxorP\lib\psr\http\message\uriInterface** |  |
| `$key` | **mixed** |  |




***

### withQueryValue



```php
public static withQueryValue(\yxorP\lib\psr\http\message\uriInterface $uri, mixed $key, mixed $value): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uri` | **\yxorP\lib\psr\http\message\uriInterface** |  |
| `$key` | **mixed** |  |
| `$value` | **mixed** |  |




***

### withQueryValues



```php
public static withQueryValues(\yxorP\lib\psr\http\message\uriInterface $uri, array $keyValueArray): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uri` | **\yxorP\lib\psr\http\message\uriInterface** |  |
| `$keyValueArray` | **array** |  |




***

### fromParts



```php
public static fromParts(array $parts): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$parts` | **array** |  |




***

### composeComponents



```php
public static composeComponents(mixed $scheme, mixed $authority, mixed $path, mixed $query, mixed $fragment): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$scheme` | **mixed** |  |
| `$authority` | **mixed** |  |
| `$path` | **mixed** |  |
| `$query` | **mixed** |  |
| `$fragment` | **mixed** |  |




***

### getFilteredQueryString



```php
private static getFilteredQueryString(\yxorP\lib\psr\http\message\uriInterface $uri, array $keys): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$uri` | **\yxorP\lib\psr\http\message\uriInterface** |  |
| `$keys` | **array** |  |




***

### generateQueryString



```php
private static generateQueryString(mixed $key, mixed $value): mixed
```



* This method is **static**.




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$key` | **mixed** |  |
| `$value` | **mixed** |  |




***

### getPort

Retrieve the port component of the URI.

```php
public getPort(): null|int
```

If a port is present, and it is non-standard for the current scheme,
this method MUST return it as an integer. If the port is the standard port
used with the current scheme, this method SHOULD return null.

If no port is present, and no scheme is present, this method MUST return
a null value.

If no port is present, but a scheme is present, this method MAY return
the standard port for that scheme, but SHOULD return null.







**Return Value:**

The URI port.



***

### getScheme

Retrieve the scheme component of the URI.

```php
public getScheme(): string
```

If no scheme is present, this method MUST return an empty string.

The value returned MUST be normalized to lowercase, per RFC 3986
Section 3.1.

The trailing ":" character is not part of the scheme and MUST NOT be
added.







**Return Value:**

The URI scheme.



***

### getAuthority

Retrieve the authority component of the URI.

```php
public getAuthority(): string
```

If no authority information is present, this method MUST return an empty
string.

The authority syntax of the URI is:

<pre>
[user-info@]host[:port]
</pre>

If the port component is not set or is the standard port for the current
scheme, it SHOULD NOT be included.







**Return Value:**

The URI authority, in "[user-info@]host[:port]" format.



***

### getPath

Retrieve the path component of the URI.

```php
public getPath(): string
```

The path can either be empty or absolute (starting with a slash) or
rootless (not starting with a slash). Implementations MUST support all
three syntaxes.

Normally, the empty path "" and absolute path "/" are considered equal as
defined in RFC 7230 Section 2.7.3. But this method MUST NOT automatically
do this normalization because in contexts with a trimmed base path, e.g.
the front controller, this difference becomes significant. It's the task
of the user to handle both "" and "/".

The value returned MUST be percent-encoded, but MUST NOT double-encode
any characters. To determine what characters to encode, please refer to
RFC 3986, Sections 2 and 3.3.

As an example, if the value should include a slash ("/") not intended as
delimiter between path segments, that value MUST be passed in encoded
form (e.g., "%2F") to the instance.







**Return Value:**

The URI path.



***

### getQuery

Retrieve the query string of the URI.

```php
public getQuery(): string
```

If no query string is present, this method MUST return an empty string.

The leading "?" character is not part of the query and MUST NOT be
added.

The value returned MUST be percent-encoded, but MUST NOT double-encode
any characters. To determine what characters to encode, please refer to
RFC 3986, Sections 2 and 3.4.

As an example, if a value in a key/value pair of the query string should
include an ampersand ("&") not intended as a delimiter between values,
that value MUST be passed in encoded form (e.g., "%26") to the instance.







**Return Value:**

The URI query string.



***

### withQuery

Return an instance with the specified query string.

```php
public withQuery(mixed $query): static
```

This method MUST retain the state of the current instance, and return
an instance that contains the specified query string.

Users can provide both encoded and decoded query characters.
Implementations ensure the correct encoding as outlined in getQuery().

An empty query string value is equivalent to removing the query string.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$query` | **mixed** | The query string to use with the new instance. |


**Return Value:**

A new instance with the specified query string.



***

### __toString

Return the string representation as a URI reference.

```php
public __toString(): string
```

Depending on which components of the URI are present, the resulting
string is either a full URI or relative reference according to RFC 3986,
Section 4.1. The method concatenates the various components of the URI,
using the appropriate delimiters:

- If a scheme is present, it MUST be suffixed by ":".
- If an authority is present, it MUST be prefixed by "//".
- The path can be concatenated without delimiters. But there are two
  cases where the path has to be adjusted to make the URI reference
  valid as PHP does not allow to throw an exception in __toString():
    - If the path is rootless and an authority is present, the path MUST
      be prefixed by "/".
    - If the path is starting with more than one "/" and no authority is
      present, the starting slashes MUST be reduced to one.
- If a query is present, it MUST be prefixed by "?".
- If a fragment is present, it MUST be prefixed by "#".









***

### getUserInfo

Retrieve the user information component of the URI.

```php
public getUserInfo(): string
```

If no user information is present, this method MUST return an empty
string.

If a user is present in the URI, this will return that value;
additionally, if the password is also present, it will be appended to the
user value, with a colon (":") separating the values.

The trailing "@" character is not part of the user information and MUST
NOT be added.







**Return Value:**

The URI user information, in "username[:password]" format.



***

### getHost

Retrieve the host component of the URI.

```php
public getHost(): string
```

If no host is present, this method MUST return an empty string.

The value returned MUST be normalized to lowercase, per RFC 3986
Section 3.2.2.







**Return Value:**

The URI host.



***

### getFragment

Retrieve the fragment component of the URI.

```php
public getFragment(): string
```

If no fragment is present, this method MUST return an empty string.

The leading "#" character is not part of the fragment and MUST NOT be
added.

The value returned MUST be percent-encoded, but MUST NOT double-encode
any characters. To determine what characters to encode, please refer to
RFC 3986, Sections 2 and 3.5.







**Return Value:**

The URI fragment.



***

### withScheme

Return an instance with the specified scheme.

```php
public withScheme(mixed $scheme): static
```

This method MUST retain the state of the current instance, and return
an instance that contains the specified scheme.

Implementations MUST support the schemes "http" and "https" case
insensitively, and MAY accommodate other schemes if required.

An empty scheme is equivalent to removing the scheme.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$scheme` | **mixed** | The scheme to use with the new instance. |


**Return Value:**

A new instance with the specified scheme.



***

### withUserInfo

Return an instance with the specified user information.

```php
public withUserInfo(mixed $user, mixed $password = null): static
```

This method MUST retain the state of the current instance, and return
an instance that contains the specified user information.

Password is optional, but the user information MUST include the
user; an empty string for the user is equivalent to removing user
information.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$user` | **mixed** | The user name to use for authority. |
| `$password` | **mixed** | The password associated with $user. |


**Return Value:**

A new instance with the specified user information.



***

### withHost

Return an instance with the specified host.

```php
public withHost(mixed $host): static
```

This method MUST retain the state of the current instance, and return
an instance that contains the specified host.

An empty host value is equivalent to removing the host.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$host` | **mixed** | The hostname to use with the new instance. |


**Return Value:**

A new instance with the specified host.



***

### withPort

Return an instance with the specified port.

```php
public withPort(mixed $port): static
```

This method MUST retain the state of the current instance, and return
an instance that contains the specified port.

Implementations MUST raise an exception for ports outside the
established TCP and UDP port ranges.

A null value provided for the port is equivalent to removing the port
information.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$port` | **mixed** | The port to use with the new instance; a null value<br />removes the port information. |


**Return Value:**

A new instance with the specified port.



***

### withPath

Return an instance with the specified path.

```php
public withPath(mixed $path): static
```

This method MUST retain the state of the current instance, and return
an instance that contains the specified path.

The path can either be empty or absolute (starting with a slash) or
rootless (not starting with a slash). Implementations MUST support all
three syntaxes.

If the path is intended to be domain-relative rather than path relative then
it must begin with a slash ("/"). Paths not starting with a slash ("/")
are assumed to be relative to some base path known to the application or
consumer.

Users can provide both encoded and decoded path characters.
Implementations ensure the correct encoding as outlined in getPath().






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$path` | **mixed** | The path to use with the new instance. |


**Return Value:**

A new instance with the specified path.



***

### withFragment

Return an instance with the specified URI fragment.

```php
public withFragment(mixed $fragment): static
```

This method MUST retain the state of the current instance, and return
an instance that contains the specified URI fragment.

Users can provide both encoded and decoded fragment characters.
Implementations ensure the correct encoding as outlined in getFragment().

An empty fragment value is equivalent to removing the fragment.






**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$fragment` | **mixed** | The fragment to use with the new instance. |


**Return Value:**

A new instance with the specified fragment.



***

### applyParts



```php
private applyParts(array $parts): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$parts` | **array** |  |




***

### filterScheme



```php
private filterScheme(mixed $scheme): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$scheme` | **mixed** |  |




***

### filterUserInfoComponent



```php
private filterUserInfoComponent(mixed $component): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$component` | **mixed** |  |




***

### filterHost



```php
private filterHost(mixed $host): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$host` | **mixed** |  |




***

### filterPort



```php
private filterPort(mixed $port): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$port` | **mixed** |  |




***

### filterPath



```php
private filterPath(mixed $path): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$path` | **mixed** |  |




***

### filterQueryAndFragment



```php
private filterQueryAndFragment(mixed $str): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$str` | **mixed** |  |




***

### removeDefaultPort



```php
private removeDefaultPort(): mixed
```











***

### validateState



```php
private validateState(): mixed
```











***

### rawurlencodeMatchZero



```php
private rawurlencodeMatchZero(array $match): mixed
```








**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$match` | **array** |  |




***


***

